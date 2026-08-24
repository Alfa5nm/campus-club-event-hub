<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!user()) {
        flash('error', 'Please sign in to continue.');
        redirect('login.php');
    }
}

function is_admin(): bool
{
    return (user()['role'] ?? '') === 'Admin';
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('The form expired. Please go back, refresh the page, and try again.');
    }
}

function executive_roles(): array
{
    return ['Executive', 'President', 'Vice President', 'Secretary', 'Treasurer'];
}

function can_manage_club(int $clubId): bool
{
    if (is_admin()) {
        return true;
    }
    if (!user()) {
        return false;
    }
    $marks = implode(',', array_fill(0, count(executive_roles()), '?'));
    $sql = "SELECT 1 FROM club_membership WHERE student_user_id = ? AND club_id = ? AND approval_status = 'Approved' AND membership_status = 'Active' AND member_role IN ($marks)";
    $stmt = db()->prepare($sql);
    $stmt->execute(array_merge([(int) user()['user_id'], $clubId], executive_roles()));
    return (bool) $stmt->fetchColumn();
}

function active_page(string $file): string
{
    return basename($_SERVER['PHP_SELF']) === $file ? 'active' : '';
}

function managed_clubs(): array
{
    if (!user()) return [];
    if (is_admin()) return db()->query("SELECT club_id, club_name FROM clubs WHERE status='Active' ORDER BY club_name")->fetchAll();
    $marks = implode(',', array_fill(0, count(executive_roles()), '?'));
    $stmt = db()->prepare("SELECT c.club_id,c.club_name FROM club_membership cm JOIN clubs c ON c.club_id=cm.club_id WHERE cm.student_user_id=? AND cm.approval_status='Approved' AND cm.membership_status='Active' AND cm.member_role IN ($marks) ORDER BY c.club_name");
    $stmt->execute(array_merge([(int) user()['user_id']], executive_roles()));
    return $stmt->fetchAll();
}

function notify_user(int $userId, string $type, string $message): void
{
    $stmt = db()->prepare('INSERT INTO notification (recipient_user_id,notification_type,message) VALUES (?,?,?)');
    $stmt->execute([$userId, mb_substr($type, 0, 80), mb_substr($message, 0, 500)]);
}

function unread_notification_count(): int
{
    if (!user()) return 0;
    $stmt = db()->prepare('SELECT COUNT(*) FROM notification WHERE recipient_user_id=? AND is_read=0');
    $stmt->execute([(int) user()['user_id']]);
    return (int) $stmt->fetchColumn();
}

function save_announcement(array $input): array
{
    $id=(int)($input['announcement_id']??0);$clubId=($input['club_id']??'')===''?null:(int)$input['club_id'];
    $message=trim($input['message']??'');$type=$clubId===null?'System Notice':'Club Notice';$status='Active';$expiry=null;
    $plain=preg_replace('/\s+/u',' ',strip_tags($message));$title=function_exists('mb_substr')?mb_substr($plain,0,72):substr($plain,0,72);$title=rtrim($title," \t\n\r\0\x0B,.;:-");
    if($message===''||$title==='')throw new InvalidArgumentException('Enter an announcement message.');
    if($clubId===null&&!is_admin())throw new DomainException('Only administrators can publish system-wide announcements.');
    if($clubId!==null&&!can_manage_club($clubId))throw new DomainException('You cannot publish for that club.');
    db()->beginTransaction();
    try{
        $prior=null;if($id){$s=db()->prepare('SELECT * FROM announcement WHERE announcement_id=? FOR UPDATE');$s->execute([$id]);$prior=$s->fetch();if(!$prior)throw new RuntimeException('Announcement not found.');if($prior['club_id']===null&&!is_admin())throw new DomainException('Not authorized.');if($prior['club_id']!==null&&!can_manage_club((int)$prior['club_id']))throw new DomainException('Not authorized.');db()->prepare('UPDATE announcement SET club_id=?,title=?,message=?,announcement_type=?,expiry_date=?,status=? WHERE announcement_id=?')->execute([$clubId,$title,$message,$type,$expiry,$status,$id]);}
        else{db()->prepare('INSERT INTO announcement (publisher_user_id,club_id,title,message,announcement_type,expiry_date,status) VALUES (?,?,?,?,?,?,?)')->execute([(int)user()['user_id'],$clubId,$title,$message,$type,$expiry,$status]);$id=(int)db()->lastInsertId();}
        $notifiedAt=$prior['notified_at']??null;$sent=0;
        if($status==='Active'&&!$notifiedAt){
            if($clubId===null)$recipients=db()->query("SELECT user_id FROM users WHERE status='Active'")->fetchAll(PDO::FETCH_COLUMN);
            else{$r=db()->prepare("SELECT student_user_id FROM club_membership WHERE club_id=? AND approval_status='Approved' AND membership_status='Active'");$r->execute([$clubId]);$recipients=$r->fetchAll(PDO::FETCH_COLUMN);}
            foreach(array_unique(array_map('intval',$recipients)) as $recipient){notify_user($recipient,$type,$title.': '.$message);$sent++;}
            db()->prepare('UPDATE announcement SET notified_at=NOW() WHERE announcement_id=?')->execute([$id]);
        }
        db()->commit();return ['announcement_id'=>$id,'status'=>$status,'recipient_count'=>$sent];
    }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
}
