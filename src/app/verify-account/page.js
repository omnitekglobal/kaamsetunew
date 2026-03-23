'use client';

import { useEffect, useState, Suspense } from 'react';
import { useSearchParams } from 'next/navigation';
import Link from 'next/link';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

function VerifyContent() {
  const searchParams = useSearchParams();
  const token = searchParams.get('token');

  const [status, setStatus] = useState('loading'); // loading | success | error
  const [message, setMessage] = useState('');

  useEffect(() => {
    if (!token) {
      setStatus('error');
      setMessage('No verification token found in the link. Please check your WhatsApp message and try again.');
      return;
    }

    const verify = async () => {
      try {
        const res = await fetch(`${API_BASE}/api/auth/verify?token=${encodeURIComponent(token)}`);
        const data = await res.json();
        if (data.success) {
          setStatus('success');
          setMessage(data.message || 'Account verified successfully!');
        } else {
          setStatus('error');
          setMessage(data.message || 'Verification failed. The link may have expired.');
        }
      } catch (err) {
        setStatus('error');
        setMessage('Could not connect to the server. Please try again.');
      }
    };

    verify();
  }, [token]);

  return (
    <div style={styles.card}>
      {status === 'loading' && (
        <>
          <div style={styles.spinner} />
          <h2 style={styles.title}>Verifying your account…</h2>
          <p style={styles.subtitle}>Please wait a moment.</p>
        </>
      )}

      {status === 'success' && (
        <>
          <div style={styles.iconCircle('#22c55e')}>✓</div>
          <h2 style={{ ...styles.title, color: '#22c55e' }}>Account Verified!</h2>
          <p style={styles.subtitle}>{message}</p>
          {/*<Link href="/login" style={styles.button('#22c55e')}>
            Go to Login
          </Link>*/}
        </>
      )}

      {status === 'error' && (
        <>
          <div style={styles.iconCircle('#ef4444')}>✕</div>
          <h2 style={{ ...styles.title, color: '#ef4444' }}>Verification Failed</h2>
          <p style={styles.subtitle}>{message}</p>
          <ResendSection />
        </>
      )}
    </div>
  );
}

function ResendSection() {
  const [phone, setPhone] = useState('');
  const [sending, setSending] = useState(false);
  const [resendMsg, setResendMsg] = useState('');
  const [resendOk, setResendOk] = useState(false);

  const handleResend = async (e) => {
    e.preventDefault();
    if (!phone.trim()) return;
    setSending(true);
    setResendMsg('');
    try {
      const res = await fetch(`${API_BASE}/api/auth/resend-verification`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone }),
      });
      const data = await res.json();
      setResendOk(data.success);
      setResendMsg(data.message || (data.success ? 'Link sent!' : 'Failed. Try again.'));
    } catch {
      setResendOk(false);
      setResendMsg('Could not connect. Try again.');
    } finally {
      setSending(false);
    }
  };

  return (
    <form onSubmit={handleResend} style={styles.resendForm}>
      <p style={{ ...styles.subtitle, marginTop: 20 }}>
        Need a new verification link?
      </p>
      <input
        type="tel"
        placeholder="Enter your WhatsApp number"
        value={phone}
        onChange={(e) => setPhone(e.target.value)}
        style={styles.input}
        required
      />
      <button type="submit" disabled={sending} style={styles.button('#6366f1')}>
        {sending ? 'Sending…' : 'Resend Verification Link'}
      </button>
      {resendMsg && (
        <p style={{ color: resendOk ? '#22c55e' : '#ef4444', marginTop: 10 }}>
          {resendMsg}
        </p>
      )}
    </form>
  );
}

// ── Inline styles (avoids external CSS dependency) ──────────────────────────
const styles = {
  card: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: '100vh',
    padding: '2rem',
    background: 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)',
    fontFamily: "'Inter', 'Segoe UI', sans-serif",
    color: '#e2e8f0',
    textAlign: 'center',
  },
  spinner: {
    width: 56,
    height: 56,
    border: '4px solid #334155',
    borderTop: '4px solid #6366f1',
    borderRadius: '50%',
    animation: 'spin 0.9s linear infinite',
    marginBottom: 24,
    // NOTE: add @keyframes spin to globals.css if not present
  },
  title: {
    fontSize: '1.75rem',
    fontWeight: 700,
    marginBottom: 8,
  },
  subtitle: {
    color: '#94a3b8',
    maxWidth: 420,
    lineHeight: 1.6,
  },
  iconCircle: (color) => ({
    width: 72,
    height: 72,
    borderRadius: '50%',
    background: color + '22',
    border: `3px solid ${color}`,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    fontSize: 32,
    color,
    marginBottom: 20,
    fontWeight: 700,
  }),
  button: (bg) => ({
    display: 'inline-block',
    marginTop: 24,
    padding: '0.75rem 2rem',
    background: bg,
    color: '#fff',
    borderRadius: 8,
    fontWeight: 600,
    fontSize: '1rem',
    textDecoration: 'none',
    cursor: 'pointer',
    border: 'none',
    transition: 'opacity 0.2s',
  }),
  input: {
    marginTop: 12,
    padding: '0.75rem 1rem',
    borderRadius: 8,
    border: '1px solid #334155',
    background: '#1e293b',
    color: '#e2e8f0',
    fontSize: '1rem',
    width: '100%',
    maxWidth: 320,
  },
  resendForm: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    width: '100%',
  },
};

export default function VerifyAccountPage() {
  return (
    <Suspense fallback={
      <div style={styles.card}>
        <div style={styles.spinner} />
        <p style={styles.subtitle}>Loading…</p>
      </div>
    }>
      <VerifyContent />
    </Suspense>
  );
}
