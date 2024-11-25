CREATE DATABASE IF NOT EXISTS db_cc;
USE db_cc;

CREATE TABLE IF NOT EXISTS Customer (

    customer_id INT PRIMARY KEY AUTO_INCREMENT, -- Chiave primaria
    fName VARCHAR(50) NOT NULL,
    lName VARCHAR(50) NOT NULL,
    hair ENUM ('lunghi', 'corti') NOT NULL,
    phoneN VARCHAR(15),
    password VARCHAR(255),
    gender ENUM ('maschio', 'femmina') NOT NULL,
    email VARCHAR(254)
);

CREATE TABLE IF NOT EXISTS appointment (

    appointment_id INT PRIMARY KEY AUTO_INCREMENT, -- Chiave primaria
    dateTime DATETIME DEFAULT CURRENT_TIMESTAMP, -- Campo data/ora
    customer_id INT, -- Aggiunta la chiave esterna per il legame con Customer
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS serviceCC(
    service_id INT PRIMARY KEY AUTO_INCREMENT, -- Chiave primaria
    timeTOT INT,
    freeTime INT,
    engageTime INT,
    nameS VARCHAR (50) NOT NULL,
    appointment_id INT, -- Aggiunta la chiave esterna per il legame con Appointment_id
    FOREIGN KEY (appointment_id) REFERENCES appointment(appointment_id) ON DELETE CASCADE
);