USE u482179263_checapelli;
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
        
