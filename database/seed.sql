USE campus_club_hub;

-- All student demo accounts use Password123!; administrator accounts use Admin123!.
INSERT INTO users (user_id,full_name,email,password_hash,phone,role,status) VALUES
(1,'Amina Rahman','amina.rahman@g.bracu.ac.bd','$2y$10$QdyyYxMIXiDhKa5Z5RQQleDlvTh7rHKt5F17h6pDTKVvpYl97Qqv6','01711000001','Student','Active'),
(2,'Nafis Karim','nafis.karim@g.bracu.ac.bd','$2y$10$QdyyYxMIXiDhKa5Z5RQQleDlvTh7rHKt5F17h6pDTKVvpYl97Qqv6','01711000002','Student','Active'),
(3,'Sadia Islam','sadia.islam@g.bracu.ac.bd','$2y$10$QdyyYxMIXiDhKa5Z5RQQleDlvTh7rHKt5F17h6pDTKVvpYl97Qqv6','01711000003','Student','Active'),
(4,'Campus Administrator','admin@campus.edu','$2y$10$ukKsDTOXjBZl86h9rlZG0uSNiolHOWSunE32flWnHY24fhkEnV7pC','01711000000','Admin','Active'),
(5,'Rifat Mahmud','rifat.mahmud@g.bracu.ac.bd','$2y$10$QdyyYxMIXiDhKa5Z5RQQleDlvTh7rHKt5F17h6pDTKVvpYl97Qqv6','01711000005','Student','Active'),
(6,'Faisal Mahbub','faisal.mahbub@g.bracu.ac.bd','$2y$10$QdyyYxMIXiDhKa5Z5RQQleDlvTh7rHKt5F17h6pDTKVvpYl97Qqv6','01711000006','Student','Active'),
(7,'Tarannum Diha','tarannum.diha@g.bracu.ac.bd','$2y$10$QdyyYxMIXiDhKa5Z5RQQleDlvTh7rHKt5F17h6pDTKVvpYl97Qqv6','01711000007','Student','Active'),
(8,'Mehedi Hasan','mehedi.hasan@g.bracu.ac.bd','$2y$10$QdyyYxMIXiDhKa5Z5RQQleDlvTh7rHKt5F17h6pDTKVvpYl97Qqv6','01711000008','Student','Active'),
(9,'Nusrat Jahan','nusrat.jahan@g.bracu.ac.bd','$2y$10$QdyyYxMIXiDhKa5Z5RQQleDlvTh7rHKt5F17h6pDTKVvpYl97Qqv6','01711000009','Student','Active'),
(10,'Adnan Chowdhury','adnan.chowdhury@g.bracu.ac.bd','$2y$10$QdyyYxMIXiDhKa5Z5RQQleDlvTh7rHKt5F17h6pDTKVvpYl97Qqv6','01711000010','Student','Active'),
(11,'Farzana Ahmed','farzana.ahmed@g.bracu.ac.bd','$2y$10$QdyyYxMIXiDhKa5Z5RQQleDlvTh7rHKt5F17h6pDTKVvpYl97Qqv6','01711000011','Student','Suspended'),
(12,'Tahmid Noor','tahmid.noor@g.bracu.ac.bd','$2y$10$QdyyYxMIXiDhKa5Z5RQQleDlvTh7rHKt5F17h6pDTKVvpYl97Qqv6','01711000012','Student','Deactivated'),
(13,'Student Affairs Admin','student.affairs@campus.edu','$2y$10$ukKsDTOXjBZl86h9rlZG0uSNiolHOWSunE32flWnHY24fhkEnV7pC','01711000013','Admin','Active');

INSERT INTO students (user_id,student_number,department,academic_year) VALUES
(1,'23101001','Computer Science','2025-26'),(2,'23101002','Business Administration','2025-26'),
(3,'23101003','English','2024-25'),(5,'23201005','Computer Science','2025-26'),
(6,'23204006','Economics','2025-26'),(7,'23201007','Computer Science','2025-26'),
(8,'23302008','Electrical Engineering','2026-27'),(9,'23103009','Architecture','2024-25'),
(10,'23301010','Computer Science','2026-27'),(11,'23104011','Economics','2024-25'),
(12,'23203012','English','2025-26');

INSERT INTO administrators (user_id,admin_role) VALUES
(4,'Super Administrator'),(13,'Student Affairs Moderator');

INSERT INTO student_interest (student_user_id,interest) VALUES
(1,'Technology'),(1,'Volunteering'),(1,'Entrepreneurship'),
(2,'Business'),(2,'Photography'),(2,'Public Speaking'),
(3,'Culture'),(3,'Debate'),(3,'Writing'),
(5,'Technology'),(5,'Robotics'),(5,'Research'),
(6,'Business'),(6,'Debate'),(6,'Leadership'),
(7,'Technology'),(7,'Volunteering'),(7,'Design'),
(8,'Robotics'),(8,'Music'),(9,'Photography'),(9,'Design'),
(10,'Technology'),(10,'Gaming'),(11,'Culture'),(12,'Writing');

INSERT INTO student_guidance (senior_student_user_id,junior_student_user_id,assigned_at,status) VALUES
(1,8,'2026-06-10 10:00:00','Active'),(3,12,'2026-05-22 12:30:00','Active'),
(5,10,'2026-04-15 09:00:00','Completed'),(6,8,'2026-07-02 15:00:00','Active'),
(7,9,'2026-03-18 11:15:00','Cancelled');

INSERT INTO clubs (club_id,club_name,description,category,logo,contact_information,status) VALUES
(1,'Computing Club','A community for builders, programmers, and curious problem-solvers.','Technology','assets/images/club-collaboration.jpg','computing@campus.edu','Active'),
(2,'Debate Society','Practice persuasive speaking, critical thinking, and competitive debate.','Debate','assets/images/campus-study.jpg','debate@campus.edu','Active'),
(3,'Green Campus Collective','Student-led volunteering for a cleaner and more sustainable campus.','Volunteering','assets/images/campus-walk.jpg','green@campus.edu','Active'),
(4,'Entrepreneurship Forum','Build practical ventures through founder talks, pitch practice, and peer feedback.','Business','assets/images/club-collaboration.jpg','entrepreneurs@campus.edu','Active'),
(5,'Photography Society','Document campus life while learning visual storytelling and editing.','Creative','assets/images/campus-walk.jpg','photography@campus.edu','Active'),
(6,'Robotics Club','Design and prototype intelligent machines through collaborative engineering.','Technology','assets/images/campus-study.jpg','robotics@campus.edu','Active'),
(7,'Music Circle','An inclusive home for performers, composers, and campus music lovers.','Culture','assets/images/club-collaboration.jpg','music@campus.edu','Pending'),
(8,'Sports Analytics Group','Explore sport through statistics, visualization, and responsible prediction.','Research','assets/images/campus-study.jpg','sports.analytics@campus.edu','Suspended');

INSERT INTO club_membership (membership_id,student_user_id,club_id,join_date,member_role,approval_status,membership_status) VALUES
(1,1,1,'2026-01-15 10:00:00','President','Approved','Active'),
(2,2,1,'2026-02-03 12:00:00','Member','Approved','Active'),
(3,3,2,'2026-01-22 09:30:00','Secretary','Approved','Active'),
(4,2,3,'2026-08-15 14:00:00','Member','Pending','Active'),
(5,5,1,'2026-03-10 11:00:00','Vice President','Approved','Active'),
(6,6,4,'2026-02-18 13:00:00','President','Approved','Active'),
(7,7,3,'2026-01-28 09:15:00','President','Approved','Active'),
(8,8,6,'2026-04-05 16:00:00','Secretary','Approved','Active'),
(9,9,5,'2026-02-11 10:30:00','President','Approved','Active'),
(10,10,1,'2026-08-17 12:20:00','Member','Pending','Active'),
(11,6,2,'2026-03-20 14:00:00','Member','Approved','Active'),
(12,7,1,'2026-05-12 10:00:00','Member','Approved','Active'),
(13,8,3,'2026-06-01 08:30:00','Member','Approved','Active'),
(14,9,2,'2026-07-08 15:45:00','Member','Rejected','Active'),
(15,10,6,'2026-06-21 11:00:00','Member','Approved','Active'),
(16,11,5,'2026-04-14 13:10:00','Treasurer','Approved','Removed'),
(17,12,4,'2026-03-09 12:00:00','Member','Approved','Resigned'),
(18,5,2,'2026-08-20 09:00:00','Member','Pending','Active'),
(19,2,4,'2026-07-26 16:30:00','Member','Approved','Active'),
(20,3,5,'2026-05-30 11:45:00','Member','Approved','Active');

INSERT INTO club_gallery (photo_id,club_id,photo_path,caption,uploaded_at) VALUES
(1,1,'assets/images/club-collaboration.jpg','Members at the spring build night','2026-04-12 18:00:00'),
(2,2,'assets/images/campus-study.jpg','Inter-university debate final','2026-05-18 20:00:00'),
(3,3,'assets/images/campus-walk.jpg','Volunteers preparing recycling stations','2026-06-04 09:00:00'),
(4,4,'assets/images/club-collaboration.jpg','Founder roundtable and pitch review','2026-07-12 17:00:00'),
(5,5,'assets/images/campus-walk.jpg','Golden-hour campus photo walk','2026-07-19 18:30:00'),
(6,6,'assets/images/campus-study.jpg','Prototype testing in the lab','2026-08-02 14:00:00'),
(7,1,'assets/images/campus-study.jpg','Peer programming workshop','2026-08-09 15:00:00'),
(8,2,'assets/images/club-collaboration.jpg','Weekly motion preparation','2026-08-12 16:00:00'),
(9,4,'assets/images/campus-study.jpg','Business model canvas session','2026-08-16 13:00:00'),
(10,5,'assets/images/club-collaboration.jpg','Student exhibition opening','2026-08-18 19:00:00');

INSERT INTO events (event_id,club_id,created_by_user_id,title,description,event_category,event_date,start_time,end_time,venue,maximum_participants,registration_deadline,poster,status) VALUES
(1,1,1,'Build Night: Ideas into Impact','Form a team and build a useful campus prototype in one evening.','Technology','2026-08-28','16:00:00','20:00:00','Innovation Lab',80,'2026-08-26','assets/images/club-collaboration.jpg','Upcoming'),
(2,2,3,'Open Debate Showcase','An open showcase featuring motions selected by the campus community.','Debate','2026-09-04','15:30:00','18:00:00','Main Auditorium',120,'2026-09-02','assets/images/campus-study.jpg','Upcoming'),
(3,3,7,'Campus Clean-up Morning','Volunteer with friends to make the central campus greener.','Volunteering','2026-09-10','08:00:00','11:00:00','Campus Gate 2',60,'2026-09-08','assets/images/campus-walk.jpg','Upcoming'),
(4,1,1,'Web Accessibility Workshop','A practical workshop on inclusive interfaces, semantic HTML, and keyboard testing.','Technology','2026-07-18','10:00:00','13:00:00','Lab 3',45,'2026-07-16','assets/images/campus-study.jpg','Completed'),
(5,2,3,'Inter-Department Debate Final','The championship round of the summer debate league.','Debate','2026-07-25','15:00:00','18:30:00','Main Auditorium',100,'2026-07-23','assets/images/club-collaboration.jpg','Completed'),
(6,3,7,'Tree Plantation Drive','Students planted native trees and documented long-term care assignments.','Volunteering','2026-08-02','07:30:00','11:30:00','Campus Field',70,'2026-07-31','assets/images/campus-walk.jpg','Completed'),
(7,4,6,'Startup Story Night','Student founders share honest lessons from their first products.','Business','2026-09-15','17:00:00','19:30:00','Multipurpose Hall',90,'2026-09-14','assets/images/club-collaboration.jpg','Upcoming'),
(8,5,9,'Dhaka Street Photography Walk','A guided visual storytelling walk with safety groups and editing review.','Creative','2026-09-19','06:30:00','11:00:00','Meet at Campus Gate 1',35,'2026-09-17','assets/images/campus-walk.jpg','Upcoming'),
(9,6,8,'Line-Following Robot Sprint','Build, test, and race a small autonomous line-following robot.','Technology','2026-09-24','14:00:00','18:00:00','Robotics Lab',50,'2026-09-21','assets/images/campus-study.jpg','Upcoming'),
(10,1,5,'Git and Open Source Clinic','Bring a repository and leave with a cleaner collaborative workflow.','General','2026-10-03','09:00:00',NULL,'Innovation Lab',55,'2026-10-03','assets/images/club-collaboration.jpg','Upcoming'),
(11,5,9,'Monsoon Portrait Session','Outdoor portrait practice postponed because of severe weather.','Creative','2026-08-20','15:00:00','18:00:00','Campus Courtyard',30,'2026-08-18','assets/images/campus-walk.jpg','Cancelled'),
(12,4,6,'Student Venture Expo','Draft programme for a student venture exhibition and mentor review.','Business','2026-10-12','10:00:00','16:00:00','Indoor Games Hall',150,'2026-10-10','assets/images/campus-study.jpg','Draft');

INSERT INTO event_registration (registration_id,student_user_id,event_id,registration_date,registration_status,qr_token,cancellation_reason) VALUES
(1,2,1,'2026-08-18 10:00:00','Registered',NULL,NULL),(2,1,2,'2026-08-19 11:00:00','Registered',NULL,NULL),
(3,5,4,'2026-07-10 09:00:00','Attended',NULL,NULL),(4,6,4,'2026-07-11 09:30:00','Attended',NULL,NULL),
(5,7,4,'2026-07-12 10:00:00','Absent',NULL,NULL),(6,8,5,'2026-07-15 14:00:00','Attended',NULL,NULL),
(7,2,5,'2026-07-16 16:00:00','Attended',NULL,NULL),(8,9,5,'2026-07-17 12:00:00','Cancelled',NULL,'Schedule conflict'),
(9,10,6,'2026-07-24 08:00:00','Attended',NULL,NULL),(10,1,6,'2026-07-25 08:30:00','Absent',NULL,NULL),
(11,5,1,'2026-08-19 13:00:00','Registered',NULL,NULL),(12,7,1,'2026-08-20 14:00:00','Registered',NULL,NULL),
(13,6,2,'2026-08-20 15:00:00','Registered',NULL,NULL),(14,8,3,'2026-08-21 09:00:00','Registered',NULL,NULL),
(15,2,7,'2026-08-21 11:00:00','Registered',NULL,NULL),(16,3,7,'2026-08-21 12:00:00','Registered',NULL,NULL),
(17,9,8,'2026-08-22 07:00:00','Registered',NULL,NULL),(18,1,8,'2026-08-22 08:00:00','Registered',NULL,NULL),
(19,10,9,'2026-08-22 10:00:00','Registered',NULL,NULL),(20,5,9,'2026-08-22 10:30:00','Registered',NULL,NULL),
(21,6,10,'2026-08-23 09:00:00','Registered',NULL,NULL),(22,7,10,'2026-08-23 09:30:00','Registered',NULL,NULL),
(23,2,11,'2026-08-15 12:00:00','Cancelled',NULL,'Event cancelled'),(24,3,3,'2026-08-23 15:00:00','Registered',NULL,NULL),
(25,9,3,'2026-08-24 10:00:00','Registered',NULL,NULL);

INSERT INTO attendance (attendance_id,registration_id,marked_by_membership_id,attendance_status,attendance_method,check_in_time,marked_at) VALUES
(1,3,1,'Present','Manual','2026-07-18 09:56:00','2026-07-18 09:56:00'),
(2,4,1,'Present','Manual','2026-07-18 10:02:00','2026-07-18 10:02:00'),
(3,5,1,'Absent','Manual',NULL,'2026-07-18 13:10:00'),
(4,6,3,'Present','Manual','2026-07-25 14:51:00','2026-07-25 14:51:00'),
(5,7,3,'Present','Manual','2026-07-25 14:58:00','2026-07-25 14:58:00'),
(6,9,7,'Present','Manual','2026-08-02 07:22:00','2026-08-02 07:22:00'),
(7,10,7,'Absent','Manual',NULL,'2026-08-02 11:45:00');

INSERT INTO certificate (certificate_id,attendance_id,certificate_number,issue_date,file_path,verification_code,status) VALUES
(1,1,'CH-2026-000001','2026-07-18','assets/demo-certificates/CH-2026-000001.pdf','DEMO-ACCESS-2026-000001','Active'),
(2,2,'CH-2026-000002','2026-07-18','assets/demo-certificates/CH-2026-000002.pdf','DEMO-ACCESS-2026-000002','Active'),
(3,4,'CH-2026-000004','2026-07-25','assets/demo-certificates/CH-2026-000004.pdf','DEMO-DEBATE-2026-000004','Active'),
(4,5,'CH-2026-000005','2026-07-25','assets/demo-certificates/CH-2026-000005.pdf','DEMO-DEBATE-2026-000005','Active'),
(5,7,'CH-2026-000007','2026-08-02','assets/demo-certificates/CH-2026-000007.pdf','DEMO-GREEN-2026-000007','Revoked');

INSERT INTO feedback (feedback_id,registration_id,rating,review_text,submitted_at,status) VALUES
(1,3,5,'The accessibility exercises were practical and easy to apply.','2026-07-18 18:00:00','Visible'),
(2,4,4,'Strong workshop; a little more time for testing would help.','2026-07-18 18:15:00','Visible'),
(3,6,5,'Excellent final and thoughtful judging feedback.','2026-07-25 20:00:00','Visible'),
(4,7,4,'The motions were challenging and the audience was engaged.','2026-07-25 20:10:00','Visible'),
(5,9,5,'Well organized and genuinely useful for the campus.','2026-08-02 14:00:00','Visible');

INSERT INTO announcement (announcement_id,publisher_user_id,club_id,title,message,announcement_type,published_at,expiry_date,status,notified_at) VALUES
(1,1,1,'Bring your laptop and your best campus idea','Bring your laptop and your best campus idea. Team matching will happen at the venue.','Club Notice','2026-08-20 10:00:00',NULL,'Active','2026-08-20 10:00:00'),
(2,4,NULL,'Explore clubs and events from one shared campus platform','Explore clubs and events from one shared campus platform.','System Notice','2026-08-18 09:00:00',NULL,'Active','2026-08-18 09:00:00'),
(3,3,2,'Debate showcase registration is now open','Debate showcase registration is now open. Audience seats are also available.','Club Notice','2026-08-21 12:00:00',NULL,'Active','2026-08-21 12:00:00'),
(4,7,3,'Bring gloves and a reusable water bottle','Bring gloves and a reusable water bottle for the campus clean-up morning.','Club Notice','2026-08-22 08:00:00',NULL,'Active','2026-08-22 08:00:00'),
(5,6,4,'Founder story night speaker list confirmed','Founder story night speaker list confirmed. Check the event page for venue details.','Club Notice','2026-08-22 15:00:00',NULL,'Active','2026-08-22 15:00:00'),
(6,9,5,'Monsoon portrait session has been cancelled','Monsoon portrait session has been cancelled because of severe weather.','Event Cancellation','2026-08-19 16:00:00','2026-08-22','Expired','2026-08-19 16:00:00'),
(7,8,6,'Robotics lab safety briefing','Robotics lab safety briefing draft for executive review.','Club Notice','2026-08-23 11:00:00',NULL,'Draft',NULL),
(8,13,NULL,'Previous maintenance notice','The planned maintenance window has ended.','System Notice','2026-08-10 08:00:00','2026-08-11','Removed','2026-08-10 08:00:00');

INSERT INTO notification (notification_id,recipient_user_id,notification_type,message,created_at,is_read) VALUES
(1,2,'Registration Confirmation','Your registration for Build Night is confirmed.','2026-08-18 10:01:00',0),
(2,1,'Announcement Update','The Debate Society published a new event.','2026-08-19 11:02:00',1),
(3,2,'Membership Update','Your Green Campus Collective request is awaiting review.','2026-08-15 14:01:00',0),
(4,5,'Certificate issued','Your certificate for Web Accessibility Workshop is ready to download.','2026-07-18 13:15:00',0),
(5,6,'Certificate issued','Your certificate for Web Accessibility Workshop is ready to download.','2026-07-18 13:16:00',1),
(6,8,'Certificate issued','Your certificate for Inter-Department Debate Final is ready to download.','2026-07-25 18:45:00',0),
(7,2,'Certificate issued','Your certificate for Inter-Department Debate Final is ready to download.','2026-07-25 18:46:00',1),
(8,1,'Attendance updated','You were marked absent for Tree Plantation Drive.','2026-08-02 11:46:00',0),
(9,10,'Certificate issued','Your certificate for Tree Plantation Drive was revoked after an attendance correction.','2026-08-02 11:47:00',0),
(10,1,'Club Notice','Bring your laptop and your best campus idea.','2026-08-20 10:00:00',1),
(11,2,'Club Notice','Bring your laptop and your best campus idea.','2026-08-20 10:00:00',0),
(12,5,'Club Notice','Bring your laptop and your best campus idea.','2026-08-20 10:00:00',0),
(13,7,'Club Notice','Bring your laptop and your best campus idea.','2026-08-20 10:00:00',1),
(14,3,'Club Notice','Debate showcase registration is now open.','2026-08-21 12:00:00',0),
(15,6,'Club Notice','Debate showcase registration is now open.','2026-08-21 12:00:00',0),
(16,7,'Club Notice','Bring gloves and a reusable water bottle.','2026-08-22 08:00:00',0),
(17,8,'Club Notice','Bring gloves and a reusable water bottle.','2026-08-22 08:00:00',1),
(18,2,'Club Notice','Founder story night speaker list confirmed.','2026-08-22 15:00:00',0),
(19,6,'Club Notice','Founder story night speaker list confirmed.','2026-08-22 15:00:00',1),
(20,9,'Event Cancellation','Monsoon portrait session has been cancelled because of severe weather.','2026-08-19 16:00:00',0),
(21,1,'System Notice','Explore clubs and events from one shared campus platform.','2026-08-18 09:00:00',1),
(22,2,'System Notice','Explore clubs and events from one shared campus platform.','2026-08-18 09:00:00',0),
(23,3,'System Notice','Explore clubs and events from one shared campus platform.','2026-08-18 09:00:00',1),
(24,5,'Registration Confirmation','Your registration for Line-Following Robot Sprint is confirmed.','2026-08-22 10:31:00',0),
(25,4,'System Notice','Explore clubs and events from one shared campus platform.','2026-08-18 09:00:00',1),
(26,5,'System Notice','Explore clubs and events from one shared campus platform.','2026-08-18 09:00:00',0),
(27,6,'System Notice','Explore clubs and events from one shared campus platform.','2026-08-18 09:00:00',1),
(28,7,'System Notice','Explore clubs and events from one shared campus platform.','2026-08-18 09:00:00',0),
(29,8,'System Notice','Explore clubs and events from one shared campus platform.','2026-08-18 09:00:00',0),
(30,9,'System Notice','Explore clubs and events from one shared campus platform.','2026-08-18 09:00:00',1),
(31,10,'System Notice','Explore clubs and events from one shared campus platform.','2026-08-18 09:00:00',0),
(32,13,'System Notice','Explore clubs and events from one shared campus platform.','2026-08-18 09:00:00',1),
(33,3,'Event Cancellation','Monsoon portrait session has been cancelled because of severe weather.','2026-08-19 16:00:00',1);

INSERT INTO password_reset_token (reset_id,user_id,token_hash,expires_at,used_at,created_at) VALUES
(1,11,SHA2('expired-demo-reset',256),'2026-08-01 12:00:00',NULL,'2026-08-01 11:00:00'),
(2,12,SHA2('used-demo-reset',256),'2026-08-10 12:00:00','2026-08-10 11:20:00','2026-08-10 11:00:00');
