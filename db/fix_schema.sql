-- Fix schema inconsistencies

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Update type_lycee to include parapublic
ALTER TABLE `param_lycee` MODIFY COLUMN `type_lycee` ENUM('public', 'prive', 'parapublic') NOT NULL;

-- 2. Ensure classes table has all required fields (already mostly there, but let's be sure)
-- niveau, numero, cycle_id, serie, categorie, lycee_id are already present.

-- 3. Update surveillant assignments if needed (Wait, I already created them in previous migration)

SET FOREIGN_KEY_CHECKS = 1;
