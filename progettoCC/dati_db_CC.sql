INSERT INTO serviceCC (nameS, timeTOT, freeTime, engageTime)
VALUES 
    ('Piega', 55, 0, 45),
    ('Taglio', 85, 0, 30),
    ('Colore', 115, 45, 60),
    ('Mèche - Schiariture', 145, 0, 90),
    ('Permanente', 125, 20, 70),
    ('Stiratura', 80, 20, 70),
    ('Keratina', 210, 20, 155),
    ('Colori - Mèche', 205, 45, 150),
    ('Ricostruzione', 80, 20, 25),
    ('Trattamento', 25, 20, 15);


INSERT INTO incompatible(service_id1,service_id2)
    VALUES 
        (1,1),