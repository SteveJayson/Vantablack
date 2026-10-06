-- Allow multiple identical gear entries in a player's inventory so crafting can consume duplicates.
-- Run this against the live MySQL database if the table already has the unique constraint.

SHOW INDEX FROM inventory WHERE Key_name = 'unique_inventory_item';
ALTER TABLE inventory DROP INDEX unique_inventory_item;

-- Optional sanity check:
SELECT combatant_id, gear_id, COUNT(*) AS duplicates
FROM inventory
GROUP BY combatant_id, gear_id
HAVING COUNT(*) > 1
LIMIT 20;
