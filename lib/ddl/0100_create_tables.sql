/*================================================================================*/
/* DDL SCRIPT                                                                     */
/*================================================================================*/
/*  Title    :                                                                    */
/*  FileName : mail.ecm                                                           */
/*  Platform : MySQL 5.6                                                          */
/*  Version  : Concept                                                            */
/*  Date     : zondag 29 januari 2017                                             */
/*================================================================================*/
/*================================================================================*/
/* CREATE TABLES                                                                  */
/*================================================================================*/

CREATE TABLE ELM_AUTHORIZED_DOMAIN (
  atd_domain_name VARCHAR(32) NOT NULL,
  CONSTRAINT PRIMARY_KEY PRIMARY KEY (atd_domain_name)
)
engine=innodb;

CREATE TABLE ELM_HEADER (
  ehd_id TINYINT UNSIGNED AUTO_INCREMENT NOT NULL,
  ehd_label VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  CONSTRAINT PRIMARY_KEY PRIMARY KEY (ehd_id)
)
engine=innodb;

CREATE TABLE ELM_MESSAGE (
  elm_id INT UNSIGNED AUTO_INCREMENT NOT NULL,
  cmp_id SMALLINT UNSIGNED NOT NULL,
  blb_id_body INT UNSIGNED NOT NULL,
  usr_id INT UNSIGNED,
  elm_address VARCHAR(255) NOT NULL,
  elm_name VARCHAR(255),
  elm_subject VARCHAR(255) NOT NULL,
  elm_number_from INT NOT NULL,
  elm_number_to INT NOT NULL,
  elm_number_cc INT NOT NULL,
  elm_number_bcc INT NOT NULL,
  elm_inserted TIMESTAMP DEFAULT current_timestamp NOT NULL,
  elm_pickuped TIMESTAMP DEFAULT null,
  elm_sent TIMESTAMP DEFAULT null,
  CONSTRAINT PRIMARY_KEY PRIMARY KEY (elm_id)
)
engine=innodb;

/*
COMMENT ON COLUMN ELM_MESSAGE.blb_id_body
The BLOB with the email body.
*/

/*
COMMENT ON COLUMN ELM_MESSAGE.usr_id
The ID of the user that is the transmitter.
*/

/*
COMMENT ON COLUMN ELM_MESSAGE.elm_address
The address of the transmitter.
*/

/*
COMMENT ON COLUMN ELM_MESSAGE.elm_name
The name of the transmitter.
*/

/*
COMMENT ON COLUMN ELM_MESSAGE.elm_subject
The subject of this message.
*/

/*
COMMENT ON COLUMN ELM_MESSAGE.elm_number_from
The number of From addressees.
*/

/*
COMMENT ON COLUMN ELM_MESSAGE.elm_number_to
The number of To addressees.
*/

/*
COMMENT ON COLUMN ELM_MESSAGE.elm_number_cc
The number of Cc addressees.
*/

/*
COMMENT ON COLUMN ELM_MESSAGE.elm_number_bcc
The number of Bcc addressees.
*/

/*
COMMENT ON COLUMN ELM_MESSAGE.elm_inserted
The timestamp when this message was inserted.
*/

/*
COMMENT ON COLUMN ELM_MESSAGE.elm_pickuped
The timestamp when this message was pickedup by the delivery process
*/

/*
COMMENT ON COLUMN ELM_MESSAGE.elm_sent
The timestamp when this message was actually delivered to the MTA.
*/

CREATE TABLE ELM_MESSAGE_HEADER (
  emh_id INT UNSIGNED AUTO_INCREMENT NOT NULL,
  cmp_id SMALLINT UNSIGNED NOT NULL,
  elm_id INT UNSIGNED NOT NULL,
  ehd_id TINYINT UNSIGNED NOT NULL,
  blb_id INT UNSIGNED,
  usr_id INT UNSIGNED,
  emh_address VARCHAR(255),
  emh_name VARCHAR(255),
  emh_value VARCHAR(255),
  CONSTRAINT PRIMARY_KEY PRIMARY KEY (emh_id)
)
engine=innodb;

/*
COMMENT ON COLUMN ELM_MESSAGE_HEADER.emh_address
The address (if the header is an address).
*/

/*
COMMENT ON COLUMN ELM_MESSAGE_HEADER.emh_name
The name associated wth the address.
*/

/*
COMMENT ON COLUMN ELM_MESSAGE_HEADER.emh_value
The value of the header.
*/

/*================================================================================*/
/* CREATE INDEXES                                                                 */
/*================================================================================*/

CREATE INDEX IX_ELM_MESSAGE1 ON ELM_MESSAGE (elm_pickuped);

CREATE INDEX IX_FK_ELM_MESSAGE ON ELM_MESSAGE (blb_id_body);

CREATE INDEX IX_FK_ELM_MESSAGE2 ON ELM_MESSAGE (cmp_id);

CREATE INDEX IX_FK_ELM_MESSAGE_HEADER ON ELM_MESSAGE_HEADER (ehd_id);

CREATE INDEX IX_FK_ELM_MESSAGE_HEADER1 ON ELM_MESSAGE_HEADER (usr_id);

CREATE INDEX IX_FK_ELM_MESSAGE_HEADER2 ON ELM_MESSAGE_HEADER (cmp_id);

/*================================================================================*/
/* CREATE FOREIGN KEYS                                                            */
/*================================================================================*/

ALTER TABLE ELM_MESSAGE
  ADD CONSTRAINT FK_ELM_MESSAGE_ABC_BLOB
  FOREIGN KEY (blb_id_body) REFERENCES ABC_BLOB (blb_id);

ALTER TABLE ELM_MESSAGE
  ADD CONSTRAINT FK_ELM_MESSAGE_ABC_COMPANY
  FOREIGN KEY (cmp_id) REFERENCES AUT_COMPANY (cmp_id);

ALTER TABLE ELM_MESSAGE
  ADD CONSTRAINT FK_ELM_MESSAGE_AUT_USER
  FOREIGN KEY (usr_id) REFERENCES AUT_USER (usr_id);

ALTER TABLE ELM_MESSAGE_HEADER
  ADD CONSTRAINT FK_ELM_ATTRIBUTE_VALUES_ABC_BLOB
  FOREIGN KEY (blb_id) REFERENCES ABC_BLOB (blb_id);

ALTER TABLE ELM_MESSAGE_HEADER
  ADD CONSTRAINT FK_ELM_ATTRIBUTE_VALUES_ABC_COMPANY
  FOREIGN KEY (cmp_id) REFERENCES AUT_COMPANY (cmp_id);

ALTER TABLE ELM_MESSAGE_HEADER
  ADD CONSTRAINT FK_ELM_ATTRIBUTE_VALUES_ABC_USER
  FOREIGN KEY (usr_id) REFERENCES AUT_USER (usr_id);

ALTER TABLE ELM_MESSAGE_HEADER
  ADD CONSTRAINT ELM_ATTRIBUTE_VALUES_ibfk_11
  FOREIGN KEY (ehd_id) REFERENCES ELM_HEADER (ehd_id);

ALTER TABLE ELM_MESSAGE_HEADER
  ADD CONSTRAINT ELM_ATTRIBUTE_VALUES_ibfk_10
  FOREIGN KEY (elm_id) REFERENCES ELM_MESSAGE (elm_id);
