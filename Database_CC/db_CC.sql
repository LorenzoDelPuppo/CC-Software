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
    preference ENUM ('Barbara', 'Giulia','Casuale') DEFAULT 'Casuale',
    email VARCHAR(254),
    wants_notification TINYINT(1) NOT NULL DEFAULT 1,
    user_tipe ENUM ('cliente','amministratore','operatrice') DEFAULT 'cliente', 
    nota TEXT
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

CREATE TABLE IF NOT EXISTS magazzino(
    prod_id INT PRIMARY KEY AUTO_INCREMENT, 
    nome_p VARCHAR(50) NOT NULL,
    cod_p VARCHAR(50) NOT NULL,
    QTA INT NOT NULL
);

INSERT INTO serviceCC (nameS, timeTOT, freeTime, engageTime)
VALUES 
    ('Piega', 55, 0, 45),
    ('Taglio', 45, 0, 30),
    ('Colore', 70, 45, 60),
    ('Mèche - Schiariture', 100, 0, 90),
    ('Permanente', 70, 20, 70),
    ('Stiratura', 70, 20, 70),
    ('Keratina', 135, 20, 155),
    ('Colori - Mèche', 125, 45, 150),
    ('Ricostruzione', 30, 20, 25),
    ('Trattamento', 25, 20, 15);


INSERT INTO incompatible(service_id1,service_id2)
    VALUES 
        (1,1),
        (2,2),
        (3,3),
        (3,5),
        (3,6),
        (3,7),
        (3,8),
        (4,3),
        (4,4),
        (4,5),
        (4,6),
        (4,7),
        (4,8),
        (5,3),
        (5,4),
        (5,5),
        (5,6),
        (5,7),
        (6,3),
        (6,4),
        (6,5),
        (6,6),
        (6,7),
        (6,8),
        (7,3),
        (7,4),
        (7,5),
        (7,6),
        (7,7),
        (7,8),
        (8,3),
        (8,4),
        (8,5),
        (8,6),
        (8,7),
        (8,8),
        (9,9),
        (10,10);

INSERT INTO requiredS(requiredS_id1,requiredS_id2)
    VALUES 
        (2,1),
        (3,1),
        (4,1),
        (5,1),
        (7,1),
        (8,1),
        (9,1);
        
INSERT INTO Customer (fName, lName, hair, phoneN, password, gender, preference, email, user_tipe)
VALUES
    ('nAdmin', 'cAdmin', 'corti', '12345', '$2y$10$AOvlRqqKO7MnhBMs5HioieoRbwicZMJGab7YaCjIyV1CseygrHbCK', 'maschio', 'Casuale', 'admin@gmail.com', 'amministratore'),
    ('Barbara', 'Feltrin', 'lunghi', '12345', '$2y$10$AOvlRqqKO7MnhBMs5HioieoRbwicZMJGab7YaCjIyV1CseygrHbCK', 'femmina', 'Casuale', 'barbarafeltrin73@gmail.com', 'operatrice'),
    ('Luca', 'Panontin', 'corti', '12345', '$2y$10$AOvlRqqKO7MnhBMs5HioieoRbwicZMJGab7YaCjIyV1CseygrHbCK', 'maschio', 'Casuale', 'panontinluca05@gmail.com', 'cliente');
