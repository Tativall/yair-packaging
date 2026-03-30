-- =====================================================
-- YAIR PACKAGING — Chat Support Tables
-- Ejecutar en Supabase → SQL Editor → New query
-- =====================================================

-- Tickets de soporte
CREATE TABLE IF NOT EXISTS chat_tickets (
  id          UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  cliente     TEXT NOT NULL,
  telefono    TEXT DEFAULT '',
  producto    TEXT DEFAULT '',
  cantidad    TEXT DEFAULT '',
  especial    TEXT DEFAULT '',
  status      TEXT DEFAULT 'pending',
  admin_id    TEXT DEFAULT NULL,
  admin_name  TEXT DEFAULT NULL,
  created_at  TIMESTAMPTZ DEFAULT NOW(),
  updated_at  TIMESTAMPTZ DEFAULT NOW()
);

-- Mensajes del chat
CREATE TABLE IF NOT EXISTS chat_messages (
  id          UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  ticket_id   UUID REFERENCES chat_tickets(id) ON DELETE CASCADE,
  sender      TEXT NOT NULL,
  mensaje     TEXT NOT NULL,
  created_at  TIMESTAMPTZ DEFAULT NOW()
);

-- Row Level Security
ALTER TABLE chat_tickets  ENABLE ROW LEVEL SECURITY;
ALTER TABLE chat_messages ENABLE ROW LEVEL SECURITY;

-- Políticas: acceso público (el chatbot crea tickets desde el frontend)
CREATE POLICY "chat_tickets_insert"  ON chat_tickets  FOR INSERT WITH CHECK (true);
CREATE POLICY "chat_tickets_select"  ON chat_tickets  FOR SELECT USING (true);
CREATE POLICY "chat_tickets_update"  ON chat_tickets  FOR UPDATE USING (true);
CREATE POLICY "chat_messages_insert" ON chat_messages FOR INSERT WITH CHECK (true);
CREATE POLICY "chat_messages_select" ON chat_messages FOR SELECT USING (true);

-- Activar Realtime para ambas tablas
ALTER PUBLICATION supabase_realtime ADD TABLE chat_tickets;
ALTER PUBLICATION supabase_realtime ADD TABLE chat_messages;
