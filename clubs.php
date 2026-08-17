<?php
require_once __DIR__ . '/includes/bootstrap.php';
$error=''; $editClub=null;
if (isset($_GET['edit'])) {
    require_login(); $id=(int)$_GET['edit'];
    if (!can_manage_club($id)) { flash('error','You are not authorized to manage that club.'); redirect('clubs.php'); }
    $s=db()->prepare('SELECT * FROM clubs WHERE club_id=?');$s->execute([$id]);$editClub=$s->fetch();
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_login(); verify_csrf(); $action=$_POST['action']??'';
    if ($action==='request_join') {
        $clubId=(int)($_POST['club_id']??0);
        try { db()->prepare("INSERT INTO club_membership (student_user_id,club_id,member_role,approval_status,membership_status) VALUES (?,?,'Member','Pending','Active')")->execute([(int)user()['user_id'],$clubId]); flash('success','Your membership request was sent.'); }
        catch(PDOException $e){ flash('error','You already have a membership or pending request for this club.'); }
        redirect('clubs.php');
    }
    if ($action==='save') {
        $id=(int)($_POST['club_id']??0);
        if (($id && !can_manage_club($id)) || (!$id && !is_admin())) { http_response_code(403); exit('Not authorized.'); }
        $name=trim($_POST['club_name']??'');$description=trim($_POST['description']??'');$category=trim($_POST['category']??'');$contact=trim($_POST['contact_information']??'');$status=$_POST['status']??'Pending';
        if($name===''||$description===''||$category===''){$error='Club name, description, and category are required.';}
        else { if($id){db()->prepare('UPDATE clubs SET club_name=?,description=?,category=?,contact_information=?,status=? WHERE club_id=?')->execute([$name,$description,$category,$contact,$status,$id]);flash('success','Club profile updated.');}else{db()->prepare('INSERT INTO clubs (club_name,description,category,contact_information,status) VALUES (?,?,?,?,?)')->execute([$name,$description,$category,$contact,$status]);flash('success','Club created.');}redirect('clubs.php'); }
    }
}
$clubs=db()->query("SELECT c.*, COUNT(DISTINCT CASE WHEN cm.approval_status='Approved' AND cm.membership_status='Active' THEN cm.membership_id END) member_count, COUNT(DISTINCT e.event_id) event_count FROM clubs c LEFT JOIN club_membership cm ON cm.club_id=c.club_id LEFT JOIN events e ON e.club_id=c.club_id GROUP BY c.club_id ORDER BY FIELD(c.status,'Active','Pending','Suspended'),c.club_name")->fetchAll();
$categories=array_values(array_unique(array_column($clubs,'category')));
$clubImages=['assets/images/club-collaboration.jpg','assets/images/campus-study.jpg','assets/images/campus-walk.jpg'];
$membershipClubIds=[];if(user()){$s=db()->prepare('SELECT club_id FROM club_membership WHERE student_user_id=?');$s->execute([(int)user()['user_id']]);$membershipClubIds=array_map('intval',$s->fetchAll(PDO::FETCH_COLUMN));}
$pageTitle='Clubs';require __DIR__.'/includes/header.php';
?>
<section class="page-shell"><div class="page-head"><div><span class="eyebrow">Campus communities</span><h2>Find your people.<br><span class="accent-script">Build something real.</span></h2><p>Explore active student organizations or manage the clubs assigned to you.</p></div><?php if(is_admin()):?><a class="button button-primary" href="clubs.php?new=1">＋ Create club</a><?php endif;?></div>
<div class="filter-bar" data-filter-scope="clubs"><label class="search-field"><span>⌕</span><input type="search" data-live-search placeholder="Search clubs or interests…"></label><div class="filter-chips"><button class="filter-chip active" data-filter="all">All</button><?php foreach($categories as $category):?><button class="filter-chip" data-filter="<?=e(strtolower($category))?>"><?=e($category)?></button><?php endforeach;?></div><span class="result-count"><strong data-result-count><?=count($clubs)?></strong> clubs</span></div>
<?php if($error):?><div class="form-error"><?=e($error)?></div><?php endif;?>
<?php if((isset($_GET['new'])&&is_admin())||$editClub):?><form class="card" method="post" style="margin-bottom:28px"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="club_id" value="<?=e((string)($editClub['club_id']??''))?>"><h3><?=$editClub?'Edit club profile':'Create a club'?></h3><div class="field-grid"><div class="field"><label>Club name</label><input name="club_name" required value="<?=e($editClub['club_name']??'')?>"></div><div class="field"><label>Category</label><input name="category" required value="<?=e($editClub['category']??'')?>"></div><div class="field"><label>Contact information</label><input name="contact_information" value="<?=e($editClub['contact_information']??'')?>"></div><div class="field"><label>Status</label><select name="status"><?php foreach(['Pending','Active','Suspended'] as $st):?><option <?=$st===($editClub['status']??'Pending')?'selected':''?>><?=e($st)?></option><?php endforeach;?></select></div></div><div class="field"><label>Description</label><textarea name="description" required><?=e($editClub['description']??'')?></textarea></div><button class="button button-primary">Save club</button> <a class="button button-quiet" href="clubs.php">Cancel</a></form><?php endif;?>
<div class="grid dynamic-grid" data-filter-grid><?php foreach($clubs as $i=>$club):?><article class="card club-card reveal" data-filter-item data-category="<?=e(strtolower($club['category']))?>" data-search="<?=e(strtolower($club['club_name'].' '.$club['description'].' '.$club['category']))?>"><div class="club-cover"><img src="<?=e($clubImages[$i%count($clubImages)])?>" alt=""><span class="cover-index">0<?=$i+1?></span></div><div class="card-content"><span class="card-tag"><?=e($club['category'])?></span><h3><?=e($club['club_name'])?></h3><p class="muted clamp"><?=e($club['description'])?></p><div class="club-metrics"><span><strong><?= (int)$club['member_count']?></strong> members</span><span><strong><?= (int)$club['event_count']?></strong> events</span></div><div class="card-footer"><span class="badge" data-membership-state><?=e($club['status'])?></span><div class="actions"><?php if(user()&&!in_array((int)$club['club_id'],$membershipClubIds,true)&&$club['status']==='Active'):?><form method="post" data-ajax="api/membership.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="request_join"><input type="hidden" name="club_id" value="<?=$club['club_id']?>"><button class="button button-secondary">Request to join</button></form><?php endif;?><?php if(user()&&can_manage_club((int)$club['club_id'])):?><a class="button button-quiet" href="clubs.php?edit=<?=$club['club_id']?>">Edit</a><?php endif;?></div></div></div></article><?php endforeach;?></div><div class="empty filter-empty" hidden>No clubs match those filters.</div></section>
<?php require __DIR__.'/includes/footer.php';?>
