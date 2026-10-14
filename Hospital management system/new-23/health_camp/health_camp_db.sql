d
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


CREATE TABLE `camps` (
  `id` int(11) NOT NULL,
  `camp_name` varchar(150) NOT NULL,
  `location` varchar(200) NOT NULL,
  `village` varchar(100) DEFAULT NULL,
  `camp_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `organizer` varchar(100) DEFAULT NULL,
  `status` enum('Upcoming','Ongoing','Completed','Cancelled') DEFAULT 'Upcoming',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `camps` (`id`, `camp_name`, `location`, `village`, `camp_date`, `start_time`, `end_time`, `organizer`, `status`, `notes`, `created_at`) VALUES
(4, 'Eye Care', 'Bus Stand', 'Manki', '2026-03-28', '09:00:00', '17:00:00', 'GGI Khanna', 'Upcoming', 'hey Notes', '2026-03-27 07:12:58');


CREATE TABLE `camp_doctors` (
  `id` int(11) NOT NULL,
  `camp_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `camp_doctors` (`id`, `camp_id`, `doctor_id`) VALUES
(10, 4, 3),
(11, 4, 2),
(12, 4, 1);



CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `doctors` (`id`, `name`, `specialization`, `phone`, `created_at`) VALUES
(1, 'Dr. Rajesh Kumar', 'General Medicine', '9876543210', '2026-03-25 07:14:27'),
(2, 'Dr. Priya Sharma', 'Pediatrics', '9876543211', '2026-03-25 07:14:27'),
(3, 'Dr. Amit Singh', 'Eye Care', '9876543212', '2026-03-25 07:14:27');


CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_stock` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `medicines` (`id`, `name`, `quantity`, `unit`, `created_at`, `total_stock`) VALUES
(1, 'Paracetamol', 500, 'Tablets', '2026-03-25 07:14:27', 500),
(2, 'Amoxicillin', 195, 'Capsules', '2026-03-25 07:14:27', 200),
(3, 'ORS Packets', 300, 'Packets', '2026-03-25 07:14:27', 300),
(4, 'Eye Drops', 51, 'Bottles', '2026-03-25 07:14:27', 51),
(5, 'Vitamin C', 400, 'Tablets', '2026-03-25 07:14:27', 400);


CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `village` varchar(100) DEFAULT NULL,
  `disease` varchar(200) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `diagnosis` text DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `patients` (`id`, `name`, `age`, `gender`, `phone`, `village`, `disease`, `doctor_id`, `visit_date`, `created_at`, `diagnosis`, `prescription`, `notes`) VALUES
(2, 'Hey', 45, 'Male', '9876543210', 'Mohali', 'Pain', 3, '2026-03-27', '2026-03-27 06:18:50', 'Jeu', 'hey', 'Follow up'),
(3, 'heu', 12, 'Male', '354676876564', 'gdsg', 'fds', 3, '2026-03-27', '2026-03-27 06:50:47', 'dfgdf', 'ghrf', 'gdfg'),
(4, 'Hurrr', 23, 'Male', '98765435678', 'Samrala city', 'head', 3, '2026-03-27', '2026-03-27 07:11:13', NULL, NULL, NULL),
(5, 'manjot', 45, 'Female', '986454542', 'Gharkhana', 'Eye Pain', 3, '2026-03-27', '2026-03-27 07:13:53', NULL, NULL, NULL),
(6, 'Sandeep', 24, 'Male', '23546789', 'G', 'hE', 2, '2026-03-27', '2026-03-27 07:23:25', NULL, NULL, NULL),
(7, 'asdfr', 87, 'Male', '794561230', 'fhdj', 'Pain', 3, '2026-03-27', '2026-03-27 07:58:25', 'atsftyg', 'test', 'Test');



CREATE TABLE `patient_camps` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `camp_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `patient_camps` (`id`, `patient_id`, `camp_id`, `assigned_at`) VALUES
(3, 5, 4, '2026-03-27 07:13:53'),
(4, 6, 4, '2026-03-27 07:23:25');


CREATE TABLE `patient_prescriptions` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `medicine_name` varchar(200) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,
  `frequency` varchar(100) DEFAULT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `qty_given` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `patient_prescriptions` (`id`, `patient_id`, `medicine_id`, `medicine_name`, `dosage`, `frequency`, `duration`, `qty_given`, `created_at`) VALUES
(1, 3, 2, 'Amoxicillin', '120', 'Once daily', '5', 5, '2026-03-27 07:46:13'),
(2, 7, NULL, 'Amoxicillin', '120', 'Once daily', '5', 8, '2026-03-27 07:59:34');


CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','doctor','staff') NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `users` (`id`, `name`, `username`, `password`, `role`, `email`, `phone`, `photo`, `status`, `created_at`) VALUES
(1, 'Admin User', 'admin', '0192023a7bbd73250516f069df18b500', 'admin', 'admin@example.com', '9876543210', NULL, 'Active', '2026-03-25 07:14:27'),
(2, 'Dr. Rajesh Kumar', 'doctor1', 'b3666d14ca079417ba6c2a99f079b2ac', 'doctor', 'doctor1@example.com', '9876543211', NULL, 'Active', '2026-03-27 06:17:22'),
(3, 'Priya Staff', 'staff1', 'de9bf5643eabf80f4a56fda3bbb84483', 'staff', 'staff1@example.com', '9876543212', NULL, 'Active', '2026-03-27 06:17:22');


ALTER TABLE `camps`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `camp_doctors`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `patient_camps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment` (`patient_id`,`camp_id`),
  ADD KEY `camp_id` (`camp_id`);

ALTER TABLE `patient_prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_id` (`patient_id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

ALTER TABLE `camps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `camp_doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;


ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;


ALTER TABLE `patient_camps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;


ALTER TABLE `patient_prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;


ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;


ALTER TABLE `patient_camps`
  ADD CONSTRAINT `patient_camps_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_camps_ibfk_2` FOREIGN KEY (`camp_id`) REFERENCES `camps` (`id`) ON DELETE CASCADE;


ALTER TABLE `patient_prescriptions`
  ADD CONSTRAINT `patient_prescriptions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;
COMMIT;


