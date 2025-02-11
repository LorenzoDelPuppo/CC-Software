CREATE DATABASE IF NOT EXISTS db_cc;
USE db_cc;

-- Tabella dati clienti
CREATE TABLE IF NOT EXISTS Customer (

    customer_id INT PRIMARY KEY AUTO_INCREMENT,
    fName VARCHAR(50) NOT NULL,
    lName VARCHAR(50) NOT NULL,
    hair ENUM ('lunghi', 'corti') NOT NULL,
    phoneN VARCHAR(15),
    password VARCHAR(255),
    gender ENUM ('maschio', 'femmina') NOT NULL,
    preference ENUM ('Barbara', 'Giulia','Casuale'), 
    email VARCHAR(254)
);

-- Tabella appuntamenti
CREATE TABLE IF NOT EXISTS appointment (

    appointment_id INT PRIMARY KEY AUTO_INCREMENT, 
    dateTime DATETIME DEFAULT CURRENT_TIMESTAMP, 
    customer_id INT, 
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE CASCADE
);

-- Tabella servizi
CREATE TABLE IF NOT EXISTS serviceCC(

    service_id INT PRIMARY KEY AUTO_INCREMENT, 
    timeTOT INT,
    freeTime INT,
    engageTime INT,
    nameS VARCHAR (50) NOT NULL
); 

-- Tabella di marge
CREATE TABLE IF NOT EXISTS mergeAS (

    appointment_id INT NOT NULL,
    service_id INT NOT NULL,
    PRIMARY KEY (appointment_id, service_id),
    FOREIGN KEY (appointment_id) REFERENCES appointment(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES serviceCC(service_id) ON DELETE CASCADE
);


-- Tabella serivci per ogni appuntamento 
CREATE TABLE IF NOT EXISTS servicesOfAppointment (

    sOa_id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    service_id INT NOT NULL,
    sPera VARCHAR(50) NOT NULL,
    FOREIGN KEY (appointment_id) REFERENCES appointment(appointment_id) ON DELETE CASCADE
);



-- Tabelle di incompatibilità
CREATE TABLE IF NOT EXISTS incompatible (

    incompatible_id INT PRIMARY KEY AUTO_INCREMENT, 
    service_id1 INT NOT NULL,
    service_id2 INT NOT NULL,
    FOREIGN KEY (service_id1) REFERENCES serviceCC(service_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id2) REFERENCES serviceCC(service_id) ON DELETE CASCADE
);

-- Tabella servizi obbligatori
CREATE TABLE IF NOT EXISTS requiredS (

    req_id INT PRIMARY KEY AUTO_INCREMENT, 
    requiredS_id1 INT NOT NULL,
    requiredS_id2 INT NOT NULL,
    FOREIGN KEY (requiredS_id1) REFERENCES serviceCC(service_id) ON DELETE CASCADE,
    FOREIGN KEY (requiredS_id2) REFERENCES serviceCC(service_id) ON DELETE CASCADE
);
