--- FunkFeuer nodeman database
---
--- Create the initial database:
---    sqlite3 nodeman.db
---    > .read schema.sql
---    > .q

PRAGMA encoding = "UTF-8";

BEGIN TRANSACTION;

CREATE TABLE config (
   name VARCHAR(50) NOT NULL,
   value VARCHAR(50) NOT NULL
);

CREATE INDEX config_idx1 ON config (name);


CREATE TABLE users (
   userid INTEGER PRIMARY KEY NOT NULL,
   email VARCHAR(50) NOT NULL,
   password VARCHAR(255) NOT NULL,
   firstname VARCHAR(50) NOT NULL,
   lastname VARCHAR(50) NOT NULL,
   phone VARCHAR(50) NOT NULL,
   usergroup VARCHAR(10) NOT NULL, -- permissions [user|admin]
   lastlogin INTEGER NOT NULL,
   regdate INTEGER NOT NULL
);

CREATE UNIQUE INDEX users_idx1 ON users (email);


CREATE TABLE userattributes (
   userid INTEGER KEY NOT NULL,
   key VARCHAR(50) NOT NULL,
   value VARCHAR(50) NOT NULL,
   FOREIGN KEY(userid) REFERENCES users(userid)
);

CREATE INDEX userattributes_idx1 ON userattributes (userid);


CREATE TABLE locations (
   locationid INTEGER PRIMARY KEY NOT NULL,
   name VARCHAR(50) NOT NULL,
   maintainer INTEGER  NOT NULL,
   address VARCHAR(255) NOT NULL,
   latitude REAL NOT NULL,
   longitude REAL NOT NULL,
   status VARCHAR(10) NOT NULL, -- current status: [interested|planned|online|offline|obsolete]
   gallerylink VARCHAR(255) NOT NULL,
   createdate INTEGER NOT NULL,
   description TEXT,
   FOREIGN KEY(maintainer) REFERENCES users(userid)
);

CREATE UNIQUE INDEX locations_idx1 ON locations (name);
CREATE INDEX locations_idx2 ON locations (maintainer);


CREATE TABLE nodes (
   nodeid INTEGER PRIMARY KEY NOT NULL,
   name VARCHAR(50) NOT NULL,
   maintainer INTEGER NOT NULL,
   location INTEGER NOT NULL,
   createdate INTEGER NOT NULL,
   description TEXT,
   FOREIGN KEY(maintainer) REFERENCES users(userid),
   FOREIGN KEY(location) REFERENCES locations(locationid)
);

CREATE INDEX nodes_idx1 ON nodes (name);
CREATE INDEX nodes_idx2 ON nodes (maintainer);
CREATE INDEX nodes_idx3 ON nodes (location);


CREATE TABLE nodeattributes (
   node INTEGER KEY NOT NULL,
   key VARCHAR(50) NOT NULL,
   value VARCHAR(50) NOT NULL,
   FOREIGN KEY(node) REFERENCES nodes(nodeid)
);

CREATE INDEX nodeattributes_idx1 ON nodeattributes (node);


CREATE TABLE interfaces (
   interfaceid INTEGER PRIMARY KEY NOT NULL,
   name VARCHAR(50) NOT NULL,
   node INTEGER NOT NULL,
   category VARCHAR(10) NOT NULL, -- interface category: [copper|fiber|tunnel|wifi2.4|wifi5|wifi60]
   type VARCHAR(5) NOT NULL, -- interface type: [IPv4|IPv6]
   address VARCHAR(50) NOT NULL,
   status VARCHAR(10) NOT NULL, -- current OLSR status: [online|offline]
   ping INTEGER NOT NULL, -- smokeping: 0=disabled, 1=enabled
   description TEXT,
   FOREIGN KEY(node) REFERENCES nodes(nodeid)
);

CREATE INDEX interfaces_idx1 ON interfaces (name);
CREATE INDEX interfaces_idx2 ON interfaces (node);
CREATE INDEX interfaces_idx3 ON interfaces (category);
CREATE INDEX interfaces_idx4 ON interfaces (type);
CREATE INDEX interfaces_idx5 ON interfaces (address);


CREATE TABLE interfaceattributes (
   interface INTEGER KEY NOT NULL,
   key VARCHAR(50) NOT NULL,
   value VARCHAR(50) NOT NULL,
   FOREIGN KEY(interface) REFERENCES interfaces(interfaceid)
);

CREATE INDEX interfaceattributes_idx1 ON interfaceattributes (interface);


CREATE TABLE linkdata (
   linkid INTEGER PRIMARY KEY NOT NULL,
   fromif INTEGER NOT NULL,
   toif INTEGER NOT NULL,
   quality REAL NOT NULL,
   source VARCHAR(10) NOT NULL, -- datasource: [olsrd|manual]
   status VARCHAR(10) NOT NULL, -- [up|down]
   firstup INTEGER NOT NULL,
   lastup INTEGER NOT NULL,
   FOREIGN KEY(fromif) REFERENCES interfaces(interfaceid),
   FOREIGN KEY(toif) REFERENCES interfaces(interfaceid)
);

CREATE INDEX linkdata_idx1 ON linkdata (fromif);
CREATE INDEX linkdata_idx2 ON linkdata (toif);


---
--- INITIAL DATA
---

INSERT INTO "config" VALUES('cache.directory', 'tmp/');
INSERT INTO "config" VALUES('security.https_only','true');
INSERT INTO "config" VALUES('title','FunkFeuer Graz');
INSERT INTO "config" VALUES('title.url','https://graz.funkfeuer.at/');
INSERT INTO "config" VALUES('mail.from','Funkfeuer Graz <noreply@example.com>');
INSERT INTO "config" VALUES('mail.smtp.host','smtp.gmail.com');
INSERT INTO "config" VALUES('mail.smtp.port','587');
INSERT INTO "config" VALUES('mail.smtp.username','');
INSERT INTO "config" VALUES('mail.smtp.password','');
INSERT INTO "config" VALUES('olsrd.address', 'localhost');

--- default account
--- email: admin@example.com, password: admin
INSERT INTO "users" VALUES(1,'admin@example.com','$2y$11$mHyBgtw2Iu0JuUpAvr.ChekNkRZMsLzmoH0/rJJQUYxEJjii.CFjS','','','','admin',0,0);
--- email: test@example.com, password: test
INSERT INTO "users" VALUES(2,'test@example.com','$2y$10$PeBgFl9OOW0BikAfo/EKPuw3GbEZaUH8oyKwg84ta23o.f.pCVZrW','','','','user',0,0);

COMMIT;
