CREATE DATABASE balance_microservice;

\c balance_microservice

CREATE TABLE balance (
	account_id serial PRIMARY KEY,
	balance varchar(255) NOT NULL
);

CREATE TABLE balance_lock (
	account_id integer PRIMARY KEY,
	is_wait_confirmation_lock boolean NOT NULL,
	expires_at timestamp without time zone NOT NULL DEFAULT NOW()
);

CREATE INDEX ON balance_lock(expires_at);
