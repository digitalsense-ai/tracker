CREATE TABLE IF NOT EXISTS prices_daily (
  ticker VARCHAR(16) NOT NULL,
  date DATE NOT NULL,
  close DECIMAL(16,6) NOT NULL,
  PRIMARY KEY (ticker, date),
  KEY idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;