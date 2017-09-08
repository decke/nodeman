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


CREATE TABLE hardware (
   hardwareid INTEGER PRIMARY KEY NOT NULL,
   vendor CHAR(50) NOT NULL,
   name CHAR(50) NOT NULL,
   url CHAR(255) NOT NULL
);


CREATE TABLE users (
   userid INTEGER PRIMARY KEY NOT NULL,
   username CHAR(50) NOT NULL,
   password CHAR(255) NOT NULL,
   email CHAR(255) NOT NULL,
   firstname CHAR(50) NOT NULL,
   lastname CHAR(50) NOT NULL,
   phone CHAR(50) NOT NULL,
   usergroup CHAR(10) NOT NULL -- permissions [user|admin]
);

CREATE UNIQUE INDEX users_idx1 ON users (username);
CREATE UNIQUE INDEX users_idx2 ON users (email);


CREATE TABLE locations (
   locationid INTEGER PRIMARY KEY NOT NULL,
   name CHAR(50) NOT NULL,
   owner INTEGER  NOT NULL,
   address CHAR(255) NOT NULL,
   latitude REAL NOT NULL,
   longitude REAL NOT NULL,
   status CHAR(10) NOT NULL,
   description BLOB,
   FOREIGN KEY(owner) REFERENCES users(userid)
);

CREATE UNIQUE INDEX locations_idx1 ON locations (name);
CREATE INDEX locations_idx2 ON locations (owner);


CREATE TABLE nodes (
   nodeid INTEGER PRIMARY KEY NOT NULL,
   name CHAR(50) NOT NULL,
   category CHAR(10) NOT NULL, -- node category: [backbone|server|client|tunnel]
   owner INTEGER NOT NULL,
   location INTEGER NOT NULL,
   hardware INTEGER NOT NULL,
   documentation BLOB,
   FOREIGN KEY(owner) REFERENCES users(userid),
   FOREIGN KEY(location) REFERENCES locations(locationid),
   FOREIGN KEY(hardware) REFERENCES hardware(hardwareid)
);

CREATE INDEX nodes_idx1 ON nodes (name);
CREATE INDEX nodes_idx2 ON nodes (owner);
CREATE INDEX nodes_idx3 ON nodes (location);
CREATE INDEX nodes_idx4 ON nodes (hardware);


CREATE TABLE interfaces (
   interfaceid INTEGER PRIMARY KEY NOT NULL,
   name CHAR(50) NOT NULL,
   node INTEGER NOT NULL,
   type CHAR(5) NOT NULL, -- interface type: [IPv4|IPv6|VPN4|VPN6]
   address CHAR(50) NOT NULL,
   linkstatus INTEGER NOT NULL, -- OLSR status: 0=down, 1=up
   status INTEGER NOT NULL, -- administrative status: 0=disabled, 1=enabled, 2=invalid
   ping INTEGER NOT NULL, -- smokeping: 0=disabled, 1=enabled
   comment CHAR(255) NOT NULL,
   FOREIGN KEY(node) REFERENCES nodes(nodeid)
);

CREATE INDEX interfaces_idx1 ON interfaces (name);
CREATE INDEX interfaces_idx2 ON interfaces (node);
CREATE INDEX interfaces_idx3 ON interfaces (type);
CREATE INDEX interfaces_idx4 ON interfaces (address);


CREATE TABLE linkdata (
   linkid INTEGER PRIMARY KEY NOT NULL,
   fromaddress INTEGER NOT NULL,
   toaddress INTEGER NOT NULL,
   quality INTEGER NOT NULL,
   type CHAR(5) NOT NULL, -- link type: [IPv4|IPv6|VPN4|VPN6]
   FOREIGN KEY(fromaddress) REFERENCES interfaces(interfaceid),
   FOREIGN KEY(toaddress) REFERENCES interfaces(interfaceid)
);

CREATE INDEX linkdata_idx1 ON linkdata (fromaddress);
CREATE INDEX linkdata_idx2 ON linkdata (toaddress);


---
--- INITIAL DATA
---

INSERT INTO "config" VALUES('schemaversion','0');
INSERT INTO "config" VALUES('security.https_only','false');
INSERT INTO "config" VALUES('title','FunkFeuer Graz');
INSERT INTO "config" VALUES('title.url','https://graz.funkfeuer.at/');

--- default account
--- username: admin, password: admin
INSERT INTO "users" VALUES(1,'admin','$2y$11$mHyBgtw2Iu0JuUpAvr.ChekNkRZMsLzmoH0/rJJQUYxEJjii.CFjS','noreply@example.com','','','','admin');

COMMIT;
