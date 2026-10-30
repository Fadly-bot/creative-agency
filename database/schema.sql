CREATE DATABASE IF NOT EXISTS creative_studio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE creative_studio;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(24) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE services (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(500) NOT NULL,
  price DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE portfolio_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(140) NOT NULL,
  category VARCHAR(100) NOT NULL,
  summary VARCHAR(500) NOT NULL,
  accent_color CHAR(7) NOT NULL DEFAULT '#F2C94C',
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  service_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  brief TEXT NOT NULL,
  deadline DATE NULL,
  price DECIMAL(12,2) UNSIGNED NOT NULL,
  status ENUM('new','review','in_progress','revision','completed','cancelled') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_orders_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
  INDEX idx_orders_user_created (user_id, created_at),
  INDEX idx_orders_status_created (status, created_at)
) ENGINE=InnoDB;

-- Demo seed: no passwords, accounts, or privileged roles are created here.
INSERT INTO services (name, description, price, sort_order) VALUES
('Identitas Visual','Logo, palet warna, dan panduan penggunaan dasar untuk bisnis.',750000,1),
('Konten Media Sosial','Template konten terarah untuk komunikasi brand di kanal sosial.',350000,2),
('Materi Promosi','Poster, menu, flyer, dan materi promosi cetak maupun digital.',250000,3),
('Kemasan Produk','Eksplorasi desain kemasan yang menonjolkan karakter produk.',600000,4),
('Presentasi Bisnis','Tata visual proposal dan presentasi agar pesan lebih mudah dipahami.',450000,5),
('Paket Brand Starter','Paket identitas awal untuk bisnis yang sedang bertumbuh.',1250000,6);

INSERT INTO portfolio_items (title,category,summary,accent_color,sort_order) VALUES
('Pasar Lokal','Identitas visual','Identitas hangat untuk ruang belanja produk pilihan lokal.','#F2C94C',1),
('Ruang Seduh','Kemasan & brand','Sistem visual sederhana untuk kopi yang akrab sehari-hari.','#D2D7C4',2),
('Tumbuh Pagi','Media sosial','Seri konten yang menyatukan edukasi dan cerita produk.','#E6B6A4',3),
('Dapur Rantau','Materi promosi','Menu dan kampanye visual untuk usaha kuliner rumahan.','#B9C9D1',4),
('Bumi Rasa','Kemasan produk','Kemasan yang menonjolkan bahan lokal dan cerita asalnya.','#E6D49E',5),
('Kebun Kecil','Identitas visual','Bahasa visual ramah untuk bisnis tanaman dan kebun kota.','#BED0B8',6);
