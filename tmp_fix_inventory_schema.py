from pathlib import Path

p = Path(r"c:\Users\ASUS\OneDrive\Desktop\Vantablack\backend\QUERY\schema_and_seed.sql")
t = p.read_text(encoding="utf-8")
old = '''CREATE TABLE inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    gear_id VARCHAR(50) NOT NULL,
    equipped BOOLEAN DEFAULT FALSE,
    acquired_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE,
    FOREIGN KEY (gear_id) REFERENCES gear_items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_inventory_item (combatant_id, gear_id)
);'''
new = '''CREATE TABLE inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    gear_id VARCHAR(50) NOT NULL,
    equipped BOOLEAN DEFAULT FALSE,
    acquired_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE,
    FOREIGN KEY (gear_id) REFERENCES gear_items(id) ON DELETE CASCADE
);'''
count = t.count(old)
if count != 2:
    raise SystemExit(f"Expected 2 matches, found {count}")

t = t.replace(old, new)
p.write_text(t, encoding="utf-8")
print(f"replaced {count} matches in {p}")
