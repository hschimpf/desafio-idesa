CREATE TABLE `adm_users` (
  `user_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_name` VARCHAR(64) NOT NULL,
  `user_email` VARCHAR(128) NOT NULL,
  `user_username` VARCHAR(32) NOT NULL,
  `user_password` BINARY(16) NOT NULL,
  `user_retry` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `user_type` ENUM('admin', 'client') NOT NULL DEFAULT 'client',
  `user_status` ENUM('new', 'active', 'disabled') NOT NULL DEFAULT 'new',
  `user_created` DATETIME NOT NULL,
  `user_createdby` BIGINT UNSIGNED NOT NULL,
  `user_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_updatedby` BIGINT UNSIGNED NOT NULL,
  `user_deleted` DATETIME NULL,
  `user_deletedby` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`user_id`),
  INDEX `users_users_fk1_idx` (`user_createdby` ASC) VISIBLE,
  INDEX `users_users_fk2_idx` (`user_updatedby` ASC) VISIBLE,
  INDEX `users_users_fk3_idx` (`user_deletedby` ASC) VISIBLE,
  CONSTRAINT `users_users_fk1`
    FOREIGN KEY (`user_createdby`)
    REFERENCES `adm_users` (`user_id`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `users_users_fk2`
    FOREIGN KEY (`user_updatedby`)
    REFERENCES `adm_users` (`user_id`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `users_users_fk3`
    FOREIGN KEY (`user_deletedby`)
    REFERENCES `adm_users` (`user_id`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE)
ENGINE = InnoDB;

CREATE TABLE `adm_clients` (
  `client_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_firstname` VARCHAR(64) NOT NULL,
  `client_lastname` VARCHAR(64) NOT NULL,
  `client_documentno` VARCHAR(16) NOT NULL,
  `client_address` VARCHAR(256) NOT NULL,
  `client_phone` VARCHAR(16) NOT NULL,
  `client_nationality` BIGINT UNSIGNED NOT NULL,
  `client_active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`client_id`),
  CONSTRAINT `adm_clients_adm_users_fk1`
    FOREIGN KEY (`client_id`)
    REFERENCES `adm_users` (`user_id`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE)
ENGINE = InnoDB;

CREATE TABLE `dat_countries` (
  `country_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `country_name` VARCHAR(64) NOT NULL,
  `country_active` BIT(1) NOT NULL DEFAULT 1,
  `country_created` DATETIME NOT NULL,
  `country_createdby` BIGINT UNSIGNED NOT NULL,
  `country_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `country_updatedby` BIGINT UNSIGNED NOT NULL,
  `country_deleted` DATETIME NULL,
  `country_deletedby` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`country_id`),
  INDEX `dat_countries_adm_users_fk1_idx` (`country_createdby` ASC) VISIBLE,
  INDEX `dat_countries_adm_users_fk2_idx` (`country_updatedby` ASC) VISIBLE,
  INDEX `dat_countries_adm_users_fk3_idx` (`country_deletedby` ASC) VISIBLE,
  CONSTRAINT `dat_countries_adm_users_fk1`
    FOREIGN KEY (`country_createdby`)
    REFERENCES `adm_users` (`user_id`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `dat_countries_adm_users_fk2`
    FOREIGN KEY (`country_updatedby`)
    REFERENCES `adm_users` (`user_id`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `dat_countries_adm_users_fk3`
    FOREIGN KEY (`country_deletedby`)
    REFERENCES `adm_users` (`user_id`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE)
ENGINE = InnoDB;

CREATE TABLE `auc_auctions` (
  `auction_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `auction_name` VARCHAR(64) NOT NULL,
  `auction_starts` DATETIME NOT NULL,
  `auction_ends` DATETIME NOT NULL,
  `auction_active` BIT(1) NOT NULL DEFAULT 1,
  `auction_created` DATETIME NOT NULL,
  `auction_createdby` BIGINT UNSIGNED NOT NULL,
  `auction_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `auction_updatedby` BIGINT UNSIGNED NOT NULL,
  `auction_deleted` DATETIME NULL,
  `auction_deletedby` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`auction_id`),
  INDEX `auc_auctions_adm_users_fk1_idx` (`auction_createdby` ASC) VISIBLE,
  INDEX `auc_auctions_adm_users_fk2_idx` (`auction_updatedby` ASC) VISIBLE,
  INDEX `auc_auctions_adm_users_fk3_idx` (`auction_deletedby` ASC) VISIBLE,
  CONSTRAINT `auc_auctions_adm_users_fk1`
    FOREIGN KEY (`auction_createdby`)
    REFERENCES `adm_users` (`user_id`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `auc_auctions_adm_users_fk2`
    FOREIGN KEY (`auction_updatedby`)
    REFERENCES `adm_users` (`user_id`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `auc_auctions_adm_users_fk3`
    FOREIGN KEY (`auction_deletedby`)
    REFERENCES `adm_users` (`user_id`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE)
ENGINE = InnoDB;

CREATE TABLE `auc_batches` (
  `batch_id` BIGINT UNSIGNED NOT NULL,
  `batch_auction` BIGINT UNSIGNED NOT NULL,
  `batch_amount_start` DECIMAL(12,2) UNSIGNED NOT NULL,
  `batch_amount_current` DECIMAL(12,2) UNSIGNED NULL,
  `batch_last_client` BIGINT UNSIGNED NULL,
  `batch_last_bid` BIGINT UNSIGNED NULL,
  `batch_active` BIT(1) NOT NULL DEFAULT 1,
  `batch_created` DATETIME NOT NULL,
  `batch_createdby` BIGINT UNSIGNED NOT NULL,
  `batch_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `batch_updatedby` BIGINT UNSIGNED NOT NULL,
  `batch_deleted` DATETIME NULL,
  `batch_deletedby` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`batch_id`, `batch_auction`),
  INDEX `auc_batches_auc_auctions_fk1_idx` (`batch_auction` ASC) VISIBLE,
  INDEX `auc_batches_adm_clients_fk1_idx` (`batch_last_client` ASC) VISIBLE,
  INDEX `auc_batches_adm_users_fk1_idx` (`batch_createdby` ASC) VISIBLE,
  INDEX `auc_batches_adm_users_fk2_idx` (`batch_updatedby` ASC) VISIBLE,
  INDEX `auc_batches_adm_users_fk3_idx` (`batch_deletedby` ASC) VISIBLE,
  CONSTRAINT `auc_batches_auc_auctions_fk1`
    FOREIGN KEY (`batch_auction`)
    REFERENCES `auc_auctions` (`auction_id`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `auc_batches_adm_clients_fk1`
    FOREIGN KEY (`batch_last_client`)
    REFERENCES `adm_clients` (`client_id`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `auc_batches_adm_users_fk1`
    FOREIGN KEY (`batch_createdby`)
    REFERENCES `adm_users` (`user_id`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `auc_batches_adm_users_fk2`
    FOREIGN KEY (`batch_updatedby`)
    REFERENCES `adm_users` (`user_id`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE,
  CONSTRAINT `auc_batches_adm_users_fk3`
    FOREIGN KEY (`batch_deletedby`)
    REFERENCES `adm_users` (`user_id`)
    ON DELETE NO ACTION
    ON UPDATE CASCADE)
ENGINE = InnoDB;

-- disable foreign checks to allow first values of createdby, updatedby columns
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
-- insert first user
INSERT INTO `adm_users` VALUES (NULL, 'Administrator', 'root@example.com', 'root', UNHEX(MD5(TO_BASE64('7nmFEWHeaX#qcaNj%gszxj8nkj*!3n'))), 0, 'admin', 'active', NOW(), 0, NOW(), 0, NULL, NULL);
-- set id to 0 (root)
UPDATE adm_users SET user_id = 0 WHERE user_id = 1;
-- re-enable foreign checks
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
