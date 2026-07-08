import React, { createContext, useContext, useState, useEffect } from 'react';
import axios from 'axios';

const AuthContext = createContext();

export const useAuth = () => useContext(AuthContext);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Check if user is logged in via session API
    const checkAuth = async () => {
      try {
        const response = await axios.get('/api/check-session');
        if (response.data && response.data.csrfToken) {
          axios.defaults.headers.common['X-CSRF-Token'] = response.data.csrfToken;
        }
        if (response.data && response.data.authenticated) {
          setUser(response.data.user);
        } else {
          setUser(null);
        }
      } catch (err) {
        if (err.response && err.response.data && err.response.data.csrfToken) {
          axios.defaults.headers.common['X-CSRF-Token'] = err.response.data.csrfToken;
        }
        setUser(null);
      } finally {
        setLoading(false);
      }
    };
    checkAuth();
  }, []);

  const login = (userData) => {
    setUser(userData);
  };

  const logout = async () => {
    try {
      await axios.post('/api/logout');
      setUser(null);
    } catch (err) {
      console.error("Logout failed", err);
    }
  };

  return (
    <AuthContext.Provider value={{ user, login, logout, loading }}>
      {!loading && children}
    </AuthContext.Provider>
  );
};
