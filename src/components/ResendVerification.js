'use client';

import { useState } from 'react';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

/**
 * ResendVerification
 * Show this component when login returns a "not verified" error.
 * Props:
 *   defaultPhone {string} – pre-fill the phone field (e.g. from the login form)
 */
export default function ResendVerification({ defaultPhone = '' }) {
  const [phone, setPhone] = useState(defaultPhone);
  const [status, setStatus] = useState('idle'); // idle | loading | success | error
  const [message, setMessage] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!phone.trim()) return;
    setStatus('loading');
    setMessage('');

    try {
      const res = await fetch(`${API_BASE}/api/auth/resend-verification`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone }),
      });
      const data = await res.json();
      setStatus(data.success ? 'success' : 'error');
      setMessage(data.message || (data.success ? 'Link sent!' : 'Failed.'));
    } catch {
      setStatus('error');
      setMessage('Network error. Please try again.');
    }
  };

  return (
    <div style={wrapperStyle}>
      <div style={boxStyle}>
        <span style={waIcon}>💬</span>
        <p style={titleStyle}>Verify via WhatsApp</p>
        <p style={descStyle}>
          Enter your registered WhatsApp number to receive a new verification link.
        </p>

        <form onSubmit={handleSubmit} style={formStyle}>
          <input
            type="tel"
            placeholder="WhatsApp number (e.g. 91XXXXXXXXXX)"
            value={phone}
            onChange={(e) => setPhone(e.target.value)}
            style={inputStyle}
            required
          />
          <button type="submit" disabled={status === 'loading'} style={btnStyle}>
            {status === 'loading' ? 'Sending…' : 'Send Verification Link'}
          </button>
        </form>

        {message && (
          <p style={{ color: status === 'success' ? '#22c55e' : '#ef4444', marginTop: 8, fontSize: 14 }}>
            {message}
          </p>
        )}
      </div>
    </div>
  );
}

const wrapperStyle = {
  marginTop: 16,
  padding: '1rem',
  borderRadius: 10,
  border: '1px solid #fbbf24',
  background: '#fef9c310',
};

const boxStyle = {
  display: 'flex',
  flexDirection: 'column',
  alignItems: 'center',
  gap: 8,
  textAlign: 'center',
};

const waIcon = { fontSize: 28 };

const titleStyle = {
  fontWeight: 700,
  fontSize: '1rem',
  color: '#fbbf24',
  margin: 0,
};

const descStyle = {
  fontSize: 13,
  color: '#94a3b8',
  maxWidth: 320,
  margin: 0,
};

const formStyle = {
  display: 'flex',
  flexDirection: 'column',
  gap: 8,
  width: '100%',
  maxWidth: 300,
};

const inputStyle = {
  padding: '0.6rem 0.9rem',
  borderRadius: 7,
  border: '1px solid #334155',
  background: '#0f172a',
  color: '#e2e8f0',
  fontSize: 14,
};

const btnStyle = {
  padding: '0.65rem 1rem',
  borderRadius: 7,
  background: '#fbbf24',
  color: '#0f172a',
  fontWeight: 700,
  border: 'none',
  cursor: 'pointer',
  fontSize: 14,
};
