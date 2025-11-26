CREATE DATABASE IF NOT EXISTS `ticketing_system`;
USE `ticketing_system`;

CREATE TABLE IF NOT EXISTS `device_tracking` (
  `id_device_tracking` INT(25) NOT NULL AUTO_INCREMENT,
  `serial_number` VARCHAR(255) NOT NULL,
  `model` VARCHAR(255) NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `os` VARCHAR(255) NOT NULL,
  `date_issued` DATE NOT NULL,
  PRIMARY KEY (`id_device_tracking`),
  UNIQUE KEY `serial_number_unique` (`serial_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `technician_assignment` (
  `id_technician_assignment` INT(25) NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(255) NOT NULL,
  `last_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id_technician_assignment`),
  UNIQUE KEY `email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ticket_intake` (
  `id_ticket_intake` INT(25) NOT NULL AUTO_INCREMENT,
  `id_device_tracking` INT(25) NOT NULL,
  `id_technician_assignment` INT(25) NOT NULL,
  `reported_by` VARCHAR(255) NOT NULL,
  `issues_description` VARCHAR(255) NOT NULL,
  `date` DATE NOT NULL,
  PRIMARY KEY (`id_ticket_intake`),
  KEY `ticket_device_idx` (`id_device_tracking`),
  KEY `ticket_technician_idx` (`id_technician_assignment`),
  CONSTRAINT `ticket_device_fk` FOREIGN KEY (`id_device_tracking`) REFERENCES `device_tracking` (`id_device_tracking`) ON DELETE CASCADE,
  CONSTRAINT `ticket_technician_fk` FOREIGN KEY (`id_technician_assignment`) REFERENCES `technician_assignment` (`id_technician_assignment`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `part_usage` (
  `id_part_usage` INT(25) NOT NULL AUTO_INCREMENT,
  `id_ticket_intake` INT(25) NOT NULL,
  `part_name` VARCHAR(255) NOT NULL,
  `quantity` INT(25) NOT NULL,
  `cost` VARCHAR(255) NOT NULL,
  `date` DATE NOT NULL,
  PRIMARY KEY (`id_part_usage`),
  KEY `part_ticket_idx` (`id_ticket_intake`),
  CONSTRAINT `part_ticket_fk` FOREIGN KEY (`id_ticket_intake`) REFERENCES `ticket_intake` (`id_ticket_intake`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `post_service_feedback` (
  `id_post_service_feedback` INT(25) NOT NULL AUTO_INCREMENT,
  `id_ticket_intake` INT(25) NOT NULL,
  `id_technician_assignment` INT(25) NOT NULL,
  `remarks` VARCHAR(255) NOT NULL,
  `status` VARCHAR(255) NOT NULL,
  `date_solved` DATE NOT NULL,
  PRIMARY KEY (`id_post_service_feedback`),
  KEY `feedback_ticket_idx` (`id_ticket_intake`),
  KEY `feedback_technician_idx` (`id_technician_assignment`),
  CONSTRAINT `feedback_ticket_fk` FOREIGN KEY (`id_ticket_intake`) REFERENCES `ticket_intake` (`id_ticket_intake`) ON DELETE CASCADE,
  CONSTRAINT `feedback_technician_fk` FOREIGN KEY (`id_technician_assignment`) REFERENCES `technician_assignment` (`id_technician_assignment`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


