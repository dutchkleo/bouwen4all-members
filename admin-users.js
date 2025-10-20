// /api/admin-users.js
import { createClient } from '@supabase/supabase-js';

const supabaseAdmin = createClient(
  process.env.SUPABASE_URL,
  process.env.SUPABASE_SERVICE_ROLE_KEY
);

export default async function handler(req, res) {
  try {
    // 1) Haal bearer token (Supabase access_token) uit de header
    const auth = req.headers.authorization || '';
    const token = auth.startsWith('Bearer ') ? auth.slice(7) : null;
    if (!token) return res.status(401).json({ error: 'No token' });

    // 2) Valideer token en haal e-mail op
    const { data: userData, error: userErr } = await supabaseAdmin.auth.getUser(token);
    if (userErr) return res.status(401).json({ error: 'Invalid token' });

    const email = userData.user?.email || '';
    const allowed = (process.env.ALLOWED_ADMIN_EMAILS || '').split(',').map(s => s.trim());
    if (!allowed.includes(email)) return res.status(403).json({ error: 'Forbidden' });

    // 3) Admin-actie: ledenlijst ophalen
    const { data, error } = await supabaseAdmin.auth.admin.listUsers();
    if (error) return res.status(500).json({ error: error.message });

    return res.status(200).json({ users: data.users });
  } catch (e) {
    return res.status(500).json({ error: e.message });
  }
}
