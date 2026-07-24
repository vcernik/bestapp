ALTER TABLE article
	ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 1 AFTER title,
	ADD INDEX idx_article_sort_order (sort_order);

SET @sort_order := 0;

UPDATE article
SET sort_order = (@sort_order := @sort_order + 1)
ORDER BY created_at DESC, id DESC;