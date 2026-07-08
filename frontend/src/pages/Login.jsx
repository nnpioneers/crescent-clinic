import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

const Login = () => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState(null);
  const navigate = useNavigate();

  const handleLogin = async (e) => {
    e.preventDefault();
    try {
      const response = await axios.post('/api/login', { username, password });
      if (response.data && response.data.success) {
        if (response.data.csrfToken) {
          axios.defaults.headers.common['X-CSRF-Token'] = response.data.csrfToken;
        }
        const role = response.data.role;
        // Redirect based on role
        if (role === 'receptionist') navigate('/receptionist');
        else if (role === 'doctor') navigate('/doctor');
        else if (role === 'pharmacist') navigate('/pharmacy');
        else if (role === 'management') navigate('/management');
      } else {
        setError(response.data?.message || 'Login failed');
      }
    } catch (err) {
      setError('An error occurred during login. Please try again.');
    }
  };

  return (
    <div className="split-login-body">
      <div className="split-login-container">
        {/* Left Side: Branding */}
        <div className="split-image-side">
          <div className="side-image-content"></div>
        </div>

        {/* Right Side: Interaction Area */}
        <div className="split-form-side">
          <div id="loginScreen" className="split-overlay-content active-view">
            <div className="login-header-v2">
              <div className="role-logo">
                <img src="/static/images/logo.jpg" alt="Clinic Logo" className="clinic-logo-img" />
              </div>
              <h2 id="loginTitle" className="role-title">Crescent Clinic and Scans</h2>
              <p id="loginSubtitle" className="role-subtitle">Sign in to access your dashboard</p>
            </div>

            {error && (
              <div className="alert alert-error">
                {error}
              </div>
            )}

            <form className="premium-form-v2" onSubmit={handleLogin}>
              <div className="form-group v2-input-group">
                <label htmlFor="username">Username</label>
                <input
                  type="text"
                  id="username"
                  name="username"
                  required
                  autoComplete="off"
                  placeholder="Enter username"
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                />
              </div>
              <div className="form-group v2-input-group">
                <label htmlFor="password">Password</label>
                <input
                  type="password"
                  id="password"
                  name="password"
                  required
                  placeholder="Enter password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                />
              </div>
              <button type="submit" className="btn-premium-action btn-full">
                <span>Sign In</span>
              </button>
            </form>
          </div>

          <div style={{ marginTop: 'auto', paddingTop: '30px', textAlign: 'center', paddingBottom: '10px' }}>
            <a
              href="/portfolio"
              style={{
                color: '#64748b',
                textDecoration: 'none',
                fontSize: '0.8rem',
                fontWeight: 500,
                letterSpacing: '0.5px',
                transition: 'color 0.3s'
              }}
              onMouseOver={(e) => (e.target.style.color = '#3b82f6')}
              onMouseOut={(e) => (e.target.style.color = '#64748b')}
            >
              @ nnp.com
            </a>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Login;
