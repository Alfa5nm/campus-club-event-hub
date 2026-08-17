USE campus_club_hub;

INSERT INTO users (user_id,full_name,email,password_hash,phone,role,status) VALUES
(1,'Amina Rahman','amina@student.edu','$2y$10$QdyyYxMIXiDhKa5Z5RQQleDlvTh7rHKt5F17h6pDTKVvpYl97Qqv6','01711000001','Student','Active'),
(2,'Nafis Karim','nafis@student.edu','$2y$10$QdyyYxMIXiDhKa5Z5RQQleDlvTh7rHKt5F17h6pDTKVvpYl97Qqv6','01711000002','Student','Active'),
(3,'Sadia Islam','sadia@student.edu','$2y$10$QdyyYxMIXiDhKa5Z5RQQleDlvTh7rHKt5F17h6pDTKVvpYl97Qqv6','01711000003','Student','Active'),
(4,'Campus Administrator','admin@campus.edu','$2y$10$ukKsDTOXjBZl86h9rlZG0uSNiolHOWSunE32flWnHY24fhkEnV7pC','01711000000','Admin','Active');

INSERT INTO students (user_id,student_number,department,academic_year) VALUES
(1,'23101001','Computer Science','2025–26'),(2,'23101002','Business Administration','2025–26'),(3,'23101003','English','2024–25');
INSERT INTO administrators (user_id,admin_role) VALUES (4,'Super Administrator');
INSERT INTO student_interest VALUES (1,'Technology'),(1,'Volunteering'),(2,'Business'),(2,'Photography'),(3,'Culture'),(3,'Debate');

INSERT INTO clubs (club_id,club_name,description,category,contact_information,status) VALUES
(1,'Computing Club','A community for builders, programmers, and curious problem-solvers.','Technology','computing@campus.edu','Active'),
(2,'Debate Society','Practice persuasive speaking, critical thinking, and competitive debate.','Debate','debate@campus.edu','Active'),
(3,'Green Campus Collective','Student-led volunteering for a cleaner and more sustainable campus.','Volunteering','green@campus.edu','Active');

INSERT INTO club_membership (membership_id,student_user_id,club_id,join_date,member_role,approval_status,membership_status) VALUES
(1,1,1,'2026-01-15 10:00:00','President','Approved','Active'),
(2,2,1,'2026-02-03 12:00:00','Member','Approved','Active'),
(3,3,2,'2026-01-22 09:30:00','Secretary','Approved','Active'),
(4,2,3,'2026-08-15 14:00:00','Member','Pending','Active');

INSERT INTO club_gallery (club_id,photo_path,caption) VALUES
(1,'uploads/gallery/computing-meetup.jpg','Members at the spring build night'),
(2,'uploads/gallery/debate-final.jpg','Inter-university debate final');

INSERT INTO events (event_id,club_id,created_by_user_id,title,description,event_category,event_date,start_time,end_time,venue,maximum_participants,registration_deadline,status) VALUES
(1,1,1,'Build Night: Ideas into Impact','Form a team and build a useful campus prototype in one evening.','Technology','2026-08-28','16:00:00','20:00:00','Innovation Lab',80,'2026-08-26','Upcoming'),
(2,2,3,'Open Debate Showcase','An open showcase featuring motions selected by the campus community.','Debate','2026-09-04','15:30:00','18:00:00','Main Auditorium',120,'2026-09-02','Upcoming'),
(3,3,1,'Campus Clean-up Morning','Volunteer with friends to make the central campus greener.','Volunteering','2026-09-10','08:00:00','11:00:00','Campus Gate 2',60,'2026-09-08','Upcoming');

INSERT INTO event_registration (registration_id,student_user_id,event_id,registration_status,qr_token) VALUES
(1,2,1,'Registered',SHA2('registration-1',256)),(2,1,2,'Registered',SHA2('registration-2',256));

INSERT INTO announcement (publisher_user_id,club_id,title,message,announcement_type,expiry_date,status) VALUES
(1,1,'Build Night registration is open','Bring your laptop and your best campus idea. Team matching will happen at the venue.','Event Update','2026-08-28','Active'),
(4,NULL,'Welcome to CampusHub','Explore clubs and events from one shared campus platform.','System Notice','2026-12-31','Active');

INSERT INTO notification (recipient_user_id,notification_type,message,is_read) VALUES
(2,'Registration Confirmation','Your registration for Build Night is confirmed.',0),
(1,'Announcement Update','The Debate Society published a new event.',1),
(2,'Membership Update','Your Green Campus Collective request is awaiting review.',0);
