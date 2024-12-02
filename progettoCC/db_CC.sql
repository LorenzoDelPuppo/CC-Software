CREATE DATABASE IF NOT EXISTS db_cc;
USE db_cc;
--Tabella dati clienti
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
--Tabella appuntamenti
CREATE TABLE IF NOT EXISTS appointment (

    appointment_id INT PRIMARY KEY AUTO_INCREMENT, -- Chiave primaria
    dateTime DATETIME DEFAULT CURRENT_TIMESTAMP, -- Campo data/ora
    customer_id INT, -- Aggiunta la chiave esterna per il legame con Customer
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE CASCADE
);
--Tabella servizi
CREATE TABLE IF NOT EXISTS serviceCC(
    service_id INT PRIMARY KEY AUTO_INCREMENT, -- Chiave primaria
    timeTOT INT,
    freeTime INT,
    engageTime INT,
    nameS VARCHAR (50) NOT NULL,
); 
-- Tabella di marge
CREATE TABLE IF NOT EXISTS mergeAS (
    appointment_id INT NOT NULL,
    service_id INT NOT NULL,
    PRIMARY KEY (appointment_id, service_id),
    FOREIGN KEY (appointment_id) REFERENCES appointment(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES serviceCC(service_id) ON DELETE CASCADE
);
--Tabelle di incompatibilità
CREATE TABLE IF NOT EXISTS incompatible (
    service_id_1 INT NOT NULL,
    service_id_2 INT NOT NULL,
    PRIMARY KEY (service_id_1, service_id_2), -- Chiave primaria composta
    FOREIGN KEY (service_id_1) REFERENCES serviceCC(service_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id_2) REFERENCES serviceCC(service_id) ON DELETE CASCADE,
    CONSTRAINT chk_no_self_incompatibility CHECK (service_id_1 != service_id_2) -- Un servizio non può essere incompatibile con sé stesso
);
