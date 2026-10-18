-- SIMSKRIPSI: struktur database
-- Catatan: database dibuat manual lewat tool (bukan lewat file ini),
-- supaya file inisimskripsisimskripsisimskripsi juga bisa di-import ke hosting InfinityFree nanti.

CREATE TABLE prodi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_prodi VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,   -- NPM / NIP / nama akun
  password VARCHAR(255) NOT NULL,         -- disimpan dalam bentuk hash
  nama VARCHAR(100) NOT NULL,
  role ENUM('admin','kaprodi','dosen','mahasiswa') NOT NULL,
  prodi_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (prodi_id) REFERENCES prodi(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE proposal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mahasiswa_id INT NOT NULL,
  dosen_id INT NULL,                      -- pembimbing, kosong saat baru diajukan
  judul VARCHAR(255) NOT NULL,
  status ENUM('diajukan','disetujui','bimbingan','seminar','sidang','selesai','ditolak')
         NOT NULL DEFAULT 'diajukan',
  tanggal_pengajuan DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (mahasiswa_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (dosen_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE status_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  proposal_id INT NOT NULL,
  status VARCHAR(20) NOT NULL,
  tanggal DATE NOT NULL,
  FOREIGN KEY (proposal_id) REFERENCES proposal(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bimbingan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  proposal_id INT NOT NULL,
  tanggal DATE NOT NULL,
  catatan TEXT NOT NULL,
  nama_file VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (proposal_id) REFERENCES proposal(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO prodi (nama_prodi) VALUES
('Teknik Informatika'), ('Manajemen'), ('Sistem Informasi');