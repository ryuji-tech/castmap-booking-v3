-- =========================================================
-- CastMap v3 スキーマ作成用SQL（v2＋ログイン機能）
--   ・v2の将来対応スキーマ（users/bookings, int管理, 論理削除）を継承
--   ・ログイン機能のため seed ユーザーを2名に増やし、案件を user_id で振り分け
--     → ログイン中ユーザーの案件だけが見える（IDOR対策）ことを実演できる
-- 使い方: phpMyAdmin の「SQL」タブに貼って実行（ローカルXAMPP / さくら共通）
-- =========================================================

-- ローカルで一からDBを作る場合のみ（さくらは管理画面で castmap_v3 相当のDBを作成）
CREATE DATABASE IF NOT EXISTS castmap_v3 DEFAULT CHARACTER SET utf8mb4;
USE castmap_v3;

-- 専用DB castmap_v3 は新規なので DROP は不要。
-- （作り直したいときだけ次の2行を手動で有効化：FKの都合で bookings を先に消す）
-- DROP TABLE IF EXISTS bookings;
-- DROP TABLE IF EXISTS users;

-- =========================================================
-- ユーザー（ログイン用）
-- =========================================================
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(50)  NOT NULL,
  email         VARCHAR(255) NOT NULL UNIQUE,             -- ログインID
  password_hash VARCHAR(255) NOT NULL,                    -- password_hash() の結果。平文は入れない
  role          TINYINT      NOT NULL DEFAULT 1,          -- 1:producer 2:manager 3:admin
  created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- seed ユーザー2名（どちらもパスワードは "password"。本番では必ず変更）
INSERT INTO users (id, name, email, password_hash, role) VALUES
(1, '田中 プロデューサー', 'tanaka@example.com',
 '$2y$10$r9vag3tHwpXJOifxu9rigelwVXGl50YgEKQmlNWiSmEC.YpQz9xaW', 1),
(2, '佐藤 マネージャー',   'sato@example.com',
 '$2y$10$r9vag3tHwpXJOifxu9rigelwVXGl50YgEKQmlNWiSmEC.YpQz9xaW', 2);

-- =========================================================
-- 案件（ブッキング）
-- =========================================================
CREATE TABLE IF NOT EXISTS bookings (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT           NOT NULL,                     -- 所有者（ログインユーザー）
  title       VARCHAR(100)  NOT NULL,
  talent_name VARCHAR(50)   NOT NULL,                     -- talents正規化は次段階
  category    TINYINT       NOT NULL DEFAULT 1,           -- 1収録 2イベント 3撮影 4打合せ 5その他
  place       VARCHAR(100)  DEFAULT NULL,
  address     VARCHAR(255)  DEFAULT NULL,                 -- 住所（地図ビュー用）
  lat         DECIMAL(10,7) DEFAULT NULL,
  lng         DECIMAL(10,7) DEFAULT NULL,
  start_at    DATETIME      NOT NULL,
  end_at      DATETIME      DEFAULT NULL,
  meet_at     DATETIME      DEFAULT NULL,
  all_day     TINYINT(1)    NOT NULL DEFAULT 0,
  status      TINYINT       NOT NULL DEFAULT 1,           -- 1打診中 2仮 3確定 4完了
  fee         INT           DEFAULT NULL,
  client      VARCHAR(100)  DEFAULT NULL,
  memo        TEXT          DEFAULT NULL,
  created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at  DATETIME      DEFAULT NULL,                 -- 論理削除（NULL=有効）
  CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_user_status (user_id, status),
  INDEX idx_start_at (start_at),
  INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- サンプル案件（user_id 1=田中 / 2=佐藤 に振り分け。ログインで見え方が変わる）
INSERT INTO bookings
  (user_id, title, talent_name, category, place, address, start_at, end_at, status, fee, client, memo) VALUES
(1, '音楽番組 収録',   '春野 凜',   1, '東京 スタジオA', '東京都渋谷区神南1-1-1', '2026-07-10 13:00:00', '2026-07-10 17:00:00', 3, 150000, '〇〇テレビ', '衣装は先方手配'),
(1, 'CM 撮影 オファー', '橘 美月',   3, '福岡 未定',      NULL,                    '2026-07-15 10:00:00', NULL,                  1, NULL,   '□□広告',  '日程調整中'),
(2, '地方イベント 出演', '夏目 悠真', 2, '大阪 ホール',   '大阪市北区梅田2-2-2',   '2026-07-12 18:00:00', '2026-07-12 20:00:00', 2, 200000, '△△プロモ', '交通費別途'),
(2, '雑誌 取材',       '星野 かえで', 4, '東京 編集部',   '東京都千代田区一番町3', '2026-07-18 15:00:00', '2026-07-18 16:30:00', 3, 80000,  '●●出版',  'カメラマン同行');
