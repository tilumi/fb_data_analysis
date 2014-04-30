CREATE TABLE if not exists posts (id varchar(50), data text, created int,
PRIMARY KEY (id)
);
CREATE TABLE if not exists comments (id varchar(50), post_id varchar(50), data text,
PRIMARY KEY (id)
);
CREATE TABLE if not exists likes (id varchar(50), post_id varchar(50), data text,
PRIMARY KEY (id)
);