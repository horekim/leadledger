-- Lead Ledger — schema. Every table carries the `ll_` prefix.
--
-- You can paste this straight into phpMyAdmin on one.com, or let install.php
-- run it for you (it rewrites the prefix to whatever db_prefix in config.php
-- says, so edit that rather than this file if you want a different one).

CREATE TABLE IF NOT EXISTS ll_users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(120)  NOT NULL,
  email         VARCHAR(190)  NOT NULL,
  password_hash VARCHAR(255)  NOT NULL,
  is_admin      TINYINT(1)    NOT NULL DEFAULT 0,
  created_at    DATETIME      NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- `category` is the top level of the archive. Its values are fixed in PHP
-- (see categories() in src/repo.php) rather than in a table of their own, so
-- there are only ever the handful the code knows about.
CREATE TABLE IF NOT EXISTS ll_ranges (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(160) NOT NULL,
  slug       VARCHAR(160) NOT NULL,
  category   VARCHAR(20)  NOT NULL DEFAULT 'fantasy',
  created_at DATETIME     NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ranges_slug (slug),
  KEY ix_ranges_name (name),
  KEY ix_ranges_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Two sets in the same range may carry the same code. The slug is what the
-- public URL addresses, so the slug is what has to be unique -- it is derived
-- from the code and given a numeric suffix when the code is already taken.
CREATE TABLE IF NOT EXISTS ll_sets (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  range_id   INT UNSIGNED NOT NULL,
  code       VARCHAR(40)  NOT NULL,
  slug       VARCHAR(60)  NOT NULL,
  name       VARCHAR(160) NOT NULL,
  created_at DATETIME     NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sets_range_slug (range_id, slug),
  KEY ix_sets_range_code (range_id, code),
  CONSTRAINT fk_sets_range FOREIGN KEY (range_id)
    REFERENCES ll_ranges (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A miniature is its photograph: that is the one required field. The code and
-- the name are both optional, and NULL where absent.
CREATE TABLE IF NOT EXISTS ll_miniatures (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  set_id     INT UNSIGNED NOT NULL,
  code       VARCHAR(60)  NULL DEFAULT NULL,
  name       VARCHAR(190) NULL DEFAULT NULL,
  photo      VARCHAR(255) NOT NULL,
  sort_index INT          NOT NULL DEFAULT 0,
  created_at DATETIME     NOT NULL,
  PRIMARY KEY (id),
  KEY ix_minis_set_sort (set_id, sort_index, id),
  CONSTRAINT fk_minis_set FOREIGN KEY (set_id)
    REFERENCES ll_sets (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One collection, the archive's own. Everyone sees the same ticks; only an
-- admin can change them, so there is no user column.
CREATE TABLE IF NOT EXISTS ll_ownership (
  miniature_id INT UNSIGNED NOT NULL,
  created_at   DATETIME     NOT NULL,
  PRIMARY KEY (miniature_id),
  CONSTRAINT fk_own_mini FOREIGN KEY (miniature_id)
    REFERENCES ll_miniatures (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
