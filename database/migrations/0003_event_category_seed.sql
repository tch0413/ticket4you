INSERT IGNORE INTO event_categories(name,description,icon)
SELECT name,description,icon FROM (
 SELECT 'Concert' AS name,'Live music and performances' AS description,'music' AS icon
 UNION ALL SELECT 'Conference','Professional talks and meetings','mic'
 UNION ALL SELECT 'Exhibition','Trade shows and exhibitions','image'
 UNION ALL SELECT 'Corporate Event','Corporate functions and meetings','business'
 UNION ALL SELECT 'Workshop','Training and educational events','education'
 UNION ALL SELECT 'Sports','Sporting events','sport'
 UNION ALL SELECT 'Convention','Conventions and gatherings','people'
 UNION ALL SELECT 'Cultural Event','Arts and cultural events','culture'
 UNION ALL SELECT 'Others','Other SICC events','tag'
 UNION ALL SELECT TRIM(category_tag),'Imported existing event category','tag' FROM events WHERE TRIM(category_tag)<>''
 UNION ALL SELECT TRIM(category_tag),'Imported existing application category','tag' FROM event_applications WHERE TRIM(category_tag)<>''
 UNION ALL SELECT TRIM(category_tag),'Imported existing interest category','tag' FROM user_preferences WHERE TRIM(category_tag)<>''
) seed;
