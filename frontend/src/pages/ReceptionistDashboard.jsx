import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';

const ReceptionistDashboard = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('register');
  
  // Data State
  const [doctors, setDoctors] = useState([]);
  const [todaysPatients, setTodaysPatients] = useState([]);
  const [stats, setStats] = useState({ total: 0 });
  const [tokenResult, setTokenResult] = useState(null);

  // Form State
  const [formData, setFormData] = useState({
    name: '', phone: '', age: '', gender: '', address: '',
    complaint: '', bp: '', temp: '', pulse: '', weight: '', height: '', spo2: '',
    doctor_id: '', manual_token: ''
  });

  const [fetchPhone, setFetchPhone] = useState('');
  const [fetchResults, setFetchResults] = useState([]);

  useEffect(() => {
    fetchDoctors();
    if (activeTab === 'patients') {
      fetchPatients();
    }
    fetchStats();
  }, [activeTab]);

  const fetchDoctors = async () => {
    try {
      const res = await axios.get('/api/doctors');
      if (res.data) setDoctors(res.data);
    } catch (err) {
      console.error('Failed to load doctors');
    }
  };

  const fetchPatients = async () => {
    try {
      const res = await axios.get('/api/patients/today');
      if (res.data) setTodaysPatients(res.data);
    } catch (err) {
      console.error('Failed to load patients');
    }
  };

  const fetchStats = async () => {
    try {
      const res = await axios.get('/api/stats/receptionist');
      if (res.data) setStats(res.data);
    } catch (err) {
      console.error('Failed to load stats');
    }
  };

  const handleInputChange = (e) => {
    setFormData({ ...formData, [e.target.id]: e.target.value });
  };

  const handleFetchPatient = async () => {
    if (!fetchPhone) return;
    try {
      const res = await axios.get(`/api/patients/search?q=${fetchPhone}`);
      setFetchResults(res.data || []);
    } catch (err) {
      console.error('Search failed');
    }
  };

  const selectFetchedPatient = (p) => {
    setFormData({
      ...formData,
      name: p.name || '', phone: p.phone || '', age: p.age || '', gender: p.gender || '',
      address: p.address || '', complaint: p.complaint || ''
    });
    setFetchResults([]);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      const res = await axios.post('/api/patients/register', formData);
      if (res.data && res.data.success) {
        setTokenResult(res.data.token);
        // Reset form but keep basic structure
        setFormData({
          name: '', phone: '', age: '', gender: '', address: '',
          complaint: '', bp: '', temp: '', pulse: '', weight: '', height: '', spo2: '',
          doctor_id: '', manual_token: ''
        });
        fetchStats();
      } else {
        alert(res.data.error || 'Failed to register patient');
      }
    } catch (err) {
      alert('Error registering patient');
    }
  };

  return (
    <div className="dashboard">
      <aside className="sidebar">
        <div className="sidebar-header">
          <div className="sidebar-logo">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
              <line x1="12" y1="8" x2="12" y2="10" />
              <line x1="11" y1="9" x2="13" y2="9" />
            </svg>
          </div>
          <div>
            <h2>Crescent Clinic</h2>
            <small>Reception Desk</small>
          </div>
        </div>
        <nav className="sidebar-nav">
          <button className={`nav-item ${activeTab === 'register' ? 'active' : ''}`} onClick={() => setActiveTab('register')}>
            Register Patient
          </button>
          <button className={`nav-item ${activeTab === 'patients' ? 'active' : ''}`} onClick={() => setActiveTab('patients')}>
            Today's Patients
          </button>
        </nav>
        <div className="sidebar-footer">
          <div className="user-info">
            <div className="user-avatar">R</div>
            <div>
              <div className="name">{user?.display_name || 'Receptionist'}</div>
              <div className="role-label">Receptionist</div>
            </div>
          </div>
          <button onClick={() => { logout(); navigate('/login'); }} className="btn btn-outline btn-sm btn-full">
            Logout
          </button>
        </div>
      </aside>

      <main className="main-content">
        {activeTab === 'register' && (
          <div className="section active">
            <div className="page-header">
              <h1>Register New Patient</h1>
              <p>Fill in the patient details and assign to a doctor</p>
            </div>

            <div className="stats-row">
              <div className="stat-card">
                <div className="stat-icon blue">...</div>
                <div>
                  <div className="stat-value">{stats.total || 0}</div>
                  <div className="stat-label">Total Today</div>
                </div>
              </div>
            </div>

            <div className="content-card" style={{ marginBottom: '20px' }}>
              <div className="card-body">
                <div style={{ display: 'flex', gap: '10px', alignItems: 'end' }}>
                  <div className="form-group" style={{ flex: 1, marginBottom: 0 }}>
                    <label>Quick Fetch (Phone or Patient ID)</label>
                    <input type="text" value={fetchPhone} onChange={(e) => setFetchPhone(e.target.value)} placeholder="Enter Phone or CCS ID" />
                  </div>
                  <button className="btn btn-outline" onClick={handleFetchPatient}>Fetch</button>
                </div>
                {fetchResults.length > 0 && (
                  <div style={{ marginTop: '15px', borderTop: '1px solid var(--border)', paddingTop: '15px' }}>
                    {fetchResults.map((p, idx) => (
                      <button key={idx} onClick={() => selectFetchedPatient(p)} className="btn btn-sm btn-outline" style={{ display: 'block', marginBottom: '5px' }}>
                        {p.name} - {p.phone}
                      </button>
                    ))}
                  </div>
                )}
              </div>
            </div>

            <div className="content-card">
              <div className="card-header">
                <h2>Patient Information</h2>
              </div>
              <div className="card-body">
                <form onSubmit={handleSubmit}>
                  <div className="form-row">
                    <div className="form-group">
                      <label htmlFor="name">Full Name *</label>
                      <input type="text" id="name" required value={formData.name} onChange={handleInputChange} />
                    </div>
                    <div className="form-group">
                      <label htmlFor="phone">Phone *</label>
                      <input type="text" id="phone" required value={formData.phone} onChange={handleInputChange} />
                    </div>
                  </div>
                  <div className="form-row">
                    <div className="form-group">
                      <label htmlFor="age">Age *</label>
                      <input type="number" id="age" required value={formData.age} onChange={handleInputChange} />
                    </div>
                    <div className="form-group">
                      <label htmlFor="gender">Gender *</label>
                      <select id="gender" required value={formData.gender} onChange={handleInputChange}>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                      </select>
                    </div>
                  </div>
                  <div className="form-group">
                    <label htmlFor="address">Address</label>
                    <input type="text" id="address" value={formData.address} onChange={handleInputChange} />
                  </div>
                  <div className="form-group">
                    <label htmlFor="complaint">Chief Complaint</label>
                    <textarea id="complaint" value={formData.complaint} onChange={handleInputChange}></textarea>
                  </div>
                  {/* Vitals */}
                  <h3 style={{ margin: '20px 0 12px' }}>Vitals</h3>
                  <div className="form-row">
                    <div className="form-group">
                      <label htmlFor="bp">Blood Pressure</label>
                      <input type="text" id="bp" value={formData.bp} onChange={handleInputChange} />
                    </div>
                    <div className="form-group">
                      <label htmlFor="temp">Temperature</label>
                      <input type="text" id="temp" value={formData.temp} onChange={handleInputChange} />
                    </div>
                  </div>
                  <div className="form-row">
                    <div className="form-group">
                      <label htmlFor="pulse">Pulse</label>
                      <input type="text" id="pulse" value={formData.pulse} onChange={handleInputChange} />
                    </div>
                    <div className="form-group">
                      <label htmlFor="weight">Weight</label>
                      <input type="text" id="weight" value={formData.weight} onChange={handleInputChange} />
                    </div>
                  </div>
                  <div className="form-row">
                    <div className="form-group">
                      <label htmlFor="height">Height</label>
                      <input type="text" id="height" value={formData.height} onChange={handleInputChange} />
                    </div>
                    <div className="form-group">
                      <label htmlFor="spo2">SpO2 (%)</label>
                      <input type="number" id="spo2" value={formData.spo2} onChange={handleInputChange} />
                    </div>
                  </div>

                  <div className="form-group">
                    <label htmlFor="doctor_id">Assign Doctor *</label>
                    <select id="doctor_id" required value={formData.doctor_id} onChange={handleInputChange}>
                      <option value="">Select Doctor</option>
                      {doctors.map(d => (
                        <option key={d.id} value={d.id}>{d.display_name}</option>
                      ))}
                    </select>
                  </div>

                  <div style={{ marginTop: '20px' }}>
                    <button type="submit" className="btn btn-primary">
                      Register Patient
                    </button>
                  </div>
                </form>
                {tokenResult && (
                  <div className="token-display" style={{ marginTop: '20px', padding: '20px', background: '#e0f2fe', borderRadius: '8px', textAlign: 'center' }}>
                    <h3>Token Generated</h3>
                    <h1 style={{ fontSize: '3rem', color: '#0369a1' }}>{tokenResult}</h1>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}

        {activeTab === 'patients' && (
          <div className="section active">
            <div className="page-header">
              <h1>Today's Patients</h1>
            </div>
            <div className="content-card">
              <div className="card-body">
                <table className="table" style={{ width: '100%', textAlign: 'left' }}>
                  <thead>
                    <tr>
                      <th>Token</th>
                      <th>Name</th>
                      <th>Age/Sex</th>
                      <th>Doctor</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    {todaysPatients.length > 0 ? todaysPatients.map(p => (
                      <tr key={p.id}>
                        <td>{p.token}</td>
                        <td>{p.name}</td>
                        <td>{p.age}/{p.gender}</td>
                        <td>{p.doctor_name}</td>
                        <td>{p.status}</td>
                      </tr>
                    )) : (
                      <tr>
                        <td colSpan="5" style={{ textAlign: 'center', padding: '20px' }}>No patients today</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        )}
      </main>
    </div>
  );
};

export default ReceptionistDashboard;
