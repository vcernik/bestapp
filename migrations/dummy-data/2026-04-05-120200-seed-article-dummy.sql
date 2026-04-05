INSERT INTO article (title, created_at)
SELECT 'First demo article', NOW() - INTERVAL 2 DAY
WHERE NOT EXISTS (SELECT 1 FROM article WHERE title = 'First demo article');

INSERT INTO article (title, created_at)
SELECT 'Second demo article', NOW() - INTERVAL 1 DAY
WHERE NOT EXISTS (SELECT 1 FROM article WHERE title = 'Second demo article');

INSERT INTO article (title, created_at)
SELECT 'Fresh article from seed', NOW()
WHERE NOT EXISTS (SELECT 1 FROM article WHERE title = 'Fresh article from seed');
