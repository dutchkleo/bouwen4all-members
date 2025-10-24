// Bestandsnaam: api/admin-users.js
// Plaats dit bestand in een map genaamd "api" in je GitHub repository

import { createClient } from '@supabase/supabase-js'

export default async function handler(req, res) {
  // Alleen POST requests toestaan
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' })
  }

  // CORS headers voor je eigen domein
  res.setHeader('Access-Control-Allow-Origin', '*')
  res.setHeader('Access-Control-Allow-Methods', 'POST')
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')

  try {
    // Haal access token uit de Authorization header
    const authHeader = req.headers.authorization
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      return res.status(401).json({ error: 'Unauthorized - No token provided' })
    }

    const userToken = authHeader.split(' ')[1]

    // Verifieer de gebruiker met de anon key
    const supabaseClient = createClient(
      process.env.SUPABASE_URL,
      process.env.SUPABASE_ANON_KEY
    )

    const { data: { user }, error: authError } = await supabaseClient.auth.getUser(userToken)

    if (authError || !user) {
      return res.status(401).json({ error: 'Unauthorized - Invalid token' })
    }

    // Check of de gebruiker admin is
    const ADMIN_EMAILS = (process.env.ADMIN_EMAILS || '').split(',').map(e => e.trim())
    
    if (!ADMIN_EMAILS.includes(user.email)) {
      return res.status(403).json({ error: 'Forbidden - Not an admin' })
    }

    // Nu gebruiken we de service role key om alle users op te halen
    const supabaseAdmin = createClient(
      process.env.SUPABASE_URL,
      process.env.SUPABASE_SERVICE_ROLE_KEY
    )

    const { data: { users }, error: listError } = await supabaseAdmin.auth.admin.listUsers()

    if (listError) {
      throw listError
    }

    return res.status(200).json({ users })

  } catch (error) {
    console.error('Admin API Error:', error)
    return res.status(500).json({ error: error.message || 'Internal server error' })
  }
}
