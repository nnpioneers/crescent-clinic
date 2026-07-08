import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';

const ManagementDashboard = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [activeSection, setActiveSection] = useState('Dashboard');
  
  // Dashboard Stats
  const [stats, setStats] = useState({ patients: 0, revenue: '0.00', profit: '0.00' });
  
  useEffect(() => {
    if (activeSection === 'Dashboard') fetchDashboardStats();
  }, [activeSection]);

  const fetchDashboardStats = async () => {
    try {
      const res = await axios.get('/api/admin/dashboard-stats?period=today');
      if (res.data) setStats(res.data);
    } catch (err) {
      console.error('Failed to load admin stats');
    }
  };

  const menuItems = [
    { id: 'Dashboard', label: 'Dashboard', icon: '■' },
    { id: 'Inventory', label: 'Inventory', icon: '☰' },
    { id: 'Patients', label: 'Patients', icon: '👥' },
    { id: 'DirectSales', label: 'Direct Medicine Sales', icon: '💊' },
    { id: 'Analytics', label: 'Analytics', icon: '📈' },
    { id: 'Master', label: 'Master Control', icon: '⚙', isGroup: true },
    { id: 'Agency', label: 'Agency Inventory', icon: '🏢', isGroup: true },
    { id: 'ControlAccess', label: 'Control Access', icon: '🔒', isGroup: true },
    { id: 'ReportDashboard', label: 'Report Dashboard', icon: '📊', isGroup: true }
  ];

  return (
    <div className="app-layout dashboard">
      <aside className="sidebar">
        <div className="sidebar-header">
          <h2>Crescent Clinic</h2>
          <div className="badge">Admin Panel</div>
        </div>
        <nav className="sidebar-nav">
          {menuItems.map(item => (
            <React.Fragment key={item.id}>
              {item.isGroup && item.id === 'Master' && (
                <div style={{ marginTop: '10px', paddingTop: '10px', borderTop: '1px solid var(--border)' }}></div>
              )}
              <button 
                className={`nav-item ${activeSection === item.id ? 'active' : ''}`} 
                onClick={() => setActiveSection(item.id)}
                style={{ textAlign: 'left', width: '100%', ...(item.isGroup ? { fontWeight: 600 } : {}) }}
              >
                <span style={{ marginRight: '8px' }}>{item.icon}</span> {item.label}
              </button>
            </React.Fragment>
          ))}
        </nav>
        <div className="sidebar-footer">
          <div className="user-info">
            <div className="user-avatar">A</div>
            <div>
              <div className="name">{user?.display_name || 'Administrator'}</div>
              <div className="role-label">Administrator</div>
            </div>
          </div>
          <button onClick={() => { logout(); navigate('/login'); }} className="btn btn-outline btn-sm btn-full">
            Logout
          </button>
        </div>
      </aside>

      <main className="main-content">
        {activeSection === 'Dashboard' && (
          <div className="section active">
            <div className="page-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>
                <h1>Overview</h1>
                <p>Live snapshot of operations.</p>
              </div>
              <select className="form-control" style={{ background: 'var(--bg-card)', padding: '8px 12px' }}>
                <option value="today">Today</option>
                <option value="yesterday">Yesterday</option>
                <option value="weekly">Last 7 Days</option>
                <option value="monthly">This Month</option>
              </select>
            </div>
            
            <div className="stats-row">
              <div className="stat-card">
                <div className="stat-icon blue">...</div>
                <div>
                  <div className="stat-value">{stats.patients}</div>
                  <div className="stat-label">Total Patients Today</div>
                </div>
              </div>
              <div className="stat-card">
                <div className="stat-icon gold">...</div>
                <div>
                  <div className="stat-value">₹{stats.revenue}</div>
                  <div className="stat-label">Total Revenue Today</div>
                </div>
              </div>
              <div className="stat-card" style={{ border: '1px solid var(--emerald)' }}>
                <div className="stat-icon emerald">...</div>
                <div>
                  <div className="stat-value" style={{ color: 'var(--emerald)' }}>₹{stats.profit}</div>
                  <div className="stat-label">Realized Profit Today</div>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Simplified Placeholders for other complex tabs */}
        {activeSection !== 'Dashboard' && (
          <div className="section active">
            <div className="page-header">
              <h1>{menuItems.find(m => m.id === activeSection)?.label}</h1>
            </div>
            <div className="content-card">
              <div className="card-body">
                <p>This module ({activeSection}) is being migrated to React. Check back soon!</p>
              </div>
            </div>
          </div>
        )}
      </main>
    </div>
  );
};

export default ManagementDashboard;
