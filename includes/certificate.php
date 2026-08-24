<?php
declare(strict_types=1);

function pdf_escape(string $text): string
{
    $text = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) ?: $text;
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

/** Small dependency-free PDF writer tailored to CampusHub certificates. */
function write_certificate_pdf(array $record, string $absolutePath): void
{
    $lines = [
        'CAMPUSHUB', 'CERTIFICATE OF PARTICIPATION',
        'This certificate is proudly presented to', $record['full_name'],
        'for attending', $record['title'],
        $record['club_name'] . '  /  ' . date('F j, Y', strtotime($record['event_date'])),
        'Certificate No. ' . $record['certificate_number'],
        'Verify: localhost/campus-club-hub/verify-certificate.php?code=' . $record['verification_code'],
    ];
    $positions = [[80,535,16],[80,470,28],[80,405,13],[80,360,25],[80,305,13],[80,262,21],[80,215,13],[80,125,11],[80,100,9]];
    $stream = "0.09 0.13 0.17 rg\n0 0 842 595 re f\n0.96 0.71 0.25 rg\n38 38 766 519 re S\n";
    foreach ($lines as $i => $line) {
        [$x,$y,$size] = $positions[$i];
        $color = $i === 1 || $i === 3 || $i === 5 ? '0.96 0.71 0.25' : '0.97 0.94 0.88';
        $stream .= "$color rg BT /F1 $size Tf $x $y Td (" . pdf_escape($line) . ") Tj ET\n";
    }
    $objects = [];
    $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
    $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
    $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>';
    $objects[] = '<< /Length ' . strlen($stream) . ">>\nstream\n$stream\nendstream";
    $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
    $pdf = "%PDF-1.4\n"; $offsets = [0];
    foreach ($objects as $i => $object) { $offsets[] = strlen($pdf); $pdf .= ($i + 1) . " 0 obj\n$object\nendobj\n"; }
    $xref = strlen($pdf); $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    for ($i=1;$i<=count($objects);$i++) $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    $pdf .= "trailer << /Size " . (count($objects)+1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    if (!is_dir(dirname($absolutePath))) mkdir(dirname($absolutePath), 0775, true);
    if (file_put_contents($absolutePath, $pdf) === false) throw new RuntimeException('Certificate file could not be written.');
}

function issue_certificate(int $attendanceId): array
{
    $stmt = db()->prepare("SELECT a.attendance_id,a.attendance_status,u.full_name,e.title,e.event_date,c.club_name FROM attendance a JOIN event_registration er ON er.registration_id=a.registration_id JOIN users u ON u.user_id=er.student_user_id JOIN events e ON e.event_id=er.event_id JOIN clubs c ON c.club_id=e.club_id WHERE a.attendance_id=?");
    $stmt->execute([$attendanceId]); $record = $stmt->fetch();
    if (!$record || $record['attendance_status'] !== 'Present') throw new RuntimeException('Only present attendees can receive certificates.');
    $existing = db()->prepare('SELECT * FROM certificate WHERE attendance_id=?'); $existing->execute([$attendanceId]);
    $certificate = $existing->fetch();
    if ($certificate) {
        db()->prepare("UPDATE certificate SET status='Active' WHERE certificate_id=?")->execute([$certificate['certificate_id']]);
        return $certificate;
    }
    $number = 'CH-' . date('Y') . '-' . str_pad((string)$attendanceId, 6, '0', STR_PAD_LEFT);
    $code = strtoupper(bin2hex(random_bytes(12)));
    $relative = 'uploads/certificates/' . $number . '.pdf';
    $record += ['certificate_number'=>$number,'verification_code'=>$code];
    write_certificate_pdf($record, dirname(__DIR__) . '/' . $relative);
    db()->prepare("INSERT INTO certificate (attendance_id,certificate_number,issue_date,file_path,verification_code,status) VALUES (?,?,CURDATE(),?,?,'Active')")->execute([$attendanceId,$number,$relative,$code]);
    return ['certificate_id'=>(int)db()->lastInsertId(),'certificate_number'=>$number,'verification_code'=>$code,'file_path'=>$relative,'status'=>'Active'];
}

function mark_attendance(int $eventId, int $registrationId, string $status): array
{
    if ($registrationId < 1 || !in_array($status, ['Present','Absent'], true)) throw new InvalidArgumentException('Invalid attendance request.');
    $event = db()->prepare('SELECT club_id,title FROM events WHERE event_id=?'); $event->execute([$eventId]); $eventRow=$event->fetch();
    if (!$eventRow) throw new RuntimeException('Event not found.');
    if (!can_manage_club((int)$eventRow['club_id'])) throw new DomainException('You are not authorized to manage this event.');
    $sql = "SELECT er.registration_id,er.student_user_id,er.registration_status,u.full_name,u.email FROM event_registration er JOIN users u ON u.user_id=er.student_user_id WHERE er.event_id=? AND er.registration_id=?";
    $stmt=db()->prepare($sql);$stmt->execute([$eventId,$registrationId]);$registration=$stmt->fetch();
    if(!$registration) throw new RuntimeException('That registration does not belong to this event.');
    if($registration['registration_status']==='Cancelled') throw new RuntimeException('Cancelled registrations cannot be checked in.');
    $markerId=null;
    if(!is_admin()){$m=db()->prepare("SELECT membership_id FROM club_membership WHERE student_user_id=? AND club_id=? AND approval_status='Approved' AND membership_status='Active' LIMIT 1");$m->execute([(int)user()['user_id'],(int)$eventRow['club_id']]);$markerId=$m->fetchColumn()?:null;}
    db()->beginTransaction();
    try {
        db()->prepare("INSERT INTO attendance (registration_id,marked_by_membership_id,attendance_status,attendance_method,check_in_time) VALUES (?,?,?,'Manual',IF(?='Present',NOW(),NULL)) ON DUPLICATE KEY UPDATE marked_by_membership_id=VALUES(marked_by_membership_id),attendance_status=VALUES(attendance_status),attendance_method='Manual',check_in_time=VALUES(check_in_time),marked_at=CURRENT_TIMESTAMP")->execute([(int)$registration['registration_id'],$markerId,$status,$status]);
        $a=db()->prepare('SELECT attendance_id FROM attendance WHERE registration_id=?');$a->execute([(int)$registration['registration_id']]);$attendanceId=(int)$a->fetchColumn();
        db()->prepare('UPDATE event_registration SET registration_status=? WHERE registration_id=?')->execute([$status==='Present'?'Attended':'Absent',(int)$registration['registration_id']]);
        $certificate=null;
        if($status==='Present'){
            $previousCertificate=db()->prepare('SELECT status FROM certificate WHERE attendance_id=?');$previousCertificate->execute([$attendanceId]);$previousCertificateStatus=$previousCertificate->fetchColumn();
            $certificate=issue_certificate($attendanceId);
            if($registration['registration_status']!=='Attended'||$previousCertificateStatus!=='Active')notify_user((int)$registration['student_user_id'],'Certificate issued','Your certificate for '.$eventRow['title'].' is ready to download.');
        } else {
            $c=db()->prepare("UPDATE certificate SET status='Revoked' WHERE attendance_id=? AND status='Active'");$c->execute([$attendanceId]);
            if($registration['registration_status']!=='Absent'||$c->rowCount())notify_user((int)$registration['student_user_id'],'Attendance updated','You were marked absent for '.$eventRow['title'].($c->rowCount()?' Your certificate was revoked.':''));
        }
        db()->commit();
        $counts=db()->prepare("SELECT SUM(er.registration_status='Registered') pending,SUM(er.registration_status='Attended') present,SUM(er.registration_status='Absent') absent,COUNT(*) total FROM event_registration er WHERE er.event_id=? AND er.registration_status!='Cancelled'");$counts->execute([$eventId]);
        return ['registration_id'=>(int)$registration['registration_id'],'student_name'=>$registration['full_name'],'status'=>$status,'certificate'=>$certificate,'counts'=>$counts->fetch()];
    } catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
}
