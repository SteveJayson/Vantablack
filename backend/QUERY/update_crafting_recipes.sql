USE aegis_db;

UPDATE crafting_recipes
SET required_materials = CASE id
    WHEN 1 THEN JSON_OBJECT('h1-01', 1)
    WHEN 2 THEN JSON_OBJECT('c1-01', 1)
    WHEN 3 THEN JSON_OBJECT('d1-01', 1)
    WHEN 4 THEN JSON_OBJECT('g1-01', 1)
    WHEN 5 THEN JSON_OBJECT('b1-01', 1)
END
WHERE id BETWEEN 1 AND 5;

SELECT id, result_gear_id, required_materials
FROM crafting_recipes
WHERE id BETWEEN 1 AND 5
ORDER BY id;