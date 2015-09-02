-- Database schema for creating nl_newsletter table.

CREATE TABLE /*_*/nl_newsletters(
	-- Primary key
	nl_id int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	nl_name varchar(64) NOT NULL,
	nl_desc varbinary(1024),
	nl_main_page_id int unsigned NOT NULL UNIQUE,
	nl_frequency varchar(50) NOT NULL,
	nl_owner_id int NOT NULL
)/*$wgDBTableOptions*/;

CREATE INDEX /*i*/nl_owner_id ON /*_*/nl_newsletters(nl_owner_id);
CREATE UNIQUE INDEX /*i*/nl_name ON /*_*/nl_newsletters (nl_name);
