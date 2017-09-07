--- FunkFeuer nodeman database
---
--- Create the initial database:
---    sqlite3 nodeman.db
---    > .read schema.sql
---    > .q

BEGIN TRANSACTION;

CREATE TABLE config (
   name CHAR(50) NOT NULL,
   value CHAR(50) NOT NULL
);

CREATE INDEX config_idx1 ON config (name);

CREATE TABLE users (
   userid INTEGER PRIMARY KEY NOT NULL,
   username CHAR(50) NOT NULL,
   password CHAR(255) NOT NULL,
   email CHAR(255) NOT NULL,
   phone CHAR(50) NOT NULL
);

CREATE INDEX users_idx1 ON users (username);


---
--- INITIAL DATA
---

INSERT INTO "config" VALUES('schemaversion','0');
INSERT INTO "config" VALUES('security.https_only','false');
INSERT INTO "config" VALUES('title','FunkFeuer Graz');
INSERT INTO "config" VALUES('title.url','https://graz.funkfeuer.at/');

--- default account
--- username: admin, password: admin
INSERT INTO "users" VALUES(1,'admin','$2y$11$mHyBgtw2Iu0JuUpAvr.ChekNkRZMsLzmoH0/rJJQUYxEJjii.CFjS','noreply@example.com','');

COMMIT;
