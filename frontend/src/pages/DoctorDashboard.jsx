import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';

const DoctorDashboard = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [queue, setQueue] = useState([]);
  const [stats, setStats] = useState({ total: 0, waiting: 0, consulted: 0 });
  const [selectedPatient, setSelectedPatient] = useState(null);
  const [isModalOpen, setIsModalOpen] = useState(false);

  // Prescription Form State
  const [prescData, setPrescData] = useState({
    fee: '0',
    requiresScan: false,
    scanType: '', scanFee: '0', scanNotes: '',
    requiresUpt: false,
    requiresInjection: false,
    injectionDetails: '', injectionCost: '0',
    prescriptionNotes: ''
  });

  useEffect(() => {
    fetchQueue();
  }, []);

  const fetchQueue = async () => {
    try {
      const res = await axios.get('/api/patients/queue'); // Assuming endpoint exists
      if (res.data) {
        setQueue(res.data.patients || []);
        setStats(res.data.stats || { total: 0, waiting: 0, consulted: 0 });
      }
    } catch (err) {
      console.error('Failed to load queue');
    }
  };

  const openPrescribeModal = (patient) => {
    setSelectedPatient(patient);
    setPrescData({
      fee: '0',
      requiresScan: false, scanType: '', scanFee: '0', scanNotes: '',
      requiresUpt: false,
      requiresInjection: false, injectionDetails: '', injectionCost: '0',
      prescriptionNotes: ''
    });
    setIsModalOpen(true);
  };

  const handlePrescChange = (field, value) => {
    setPrescData(prev => ({ ...prev, [field]: value }));
  };

  const submitPrescription = async () => {
    if (!selectedPatient) return;
    try {
      const payload = {
        patient_id: selectedPatient.id,
        ...prescData
      };
      const res = await axios.post('/api/prescribe', payload);
      if (res.data && res.data.success) {
        setIsModalOpen(false);
        fetchQueue();
      } else {
        alert('Failed to submit prescription');
      }
    } catch (err) {
      alert('Error submitting prescription');
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
            <small>Doctor Console</small>
          </div>
        </div>
        <nav className="sidebar-nav">
          <button className="nav-item active">
            Patient Queue
          </button>
        </nav>
        <div className="sidebar-footer">
          <div className="user-info">
            <div className="user-avatar">D</div>
            <div>
              <div className="name">{user?.display_name || 'Doctor'}</div>
              <div className="role-label">Doctor</div>
            </div>
          </div>
          <button onClick={() => { logout(); navigate('/login'); }} className="btn btn-outline btn-sm btn-full">
            Logout
          </button>
        </div>
      </aside>

      <main className="main-content">
        <div className="section active">
          <div className="page-header">
            <h1>Patient Queue</h1>
            <p>Waiting patients assigned to you</p>
          </div>

          <div className="stats-row">
            <div className="stat-card">
              <div className="stat-icon blue">...</div>
              <div>
                <div className="stat-value">{stats.total}</div>
                <div className="stat-label">Total Patients</div>
              </div>
            </div>
            <div className="stat-card">
              <div className="stat-icon amber">...</div>
              <div>
                <div className="stat-value">{stats.waiting}</div>
                <div className="stat-label">Waiting</div>
              </div>
            </div>
            <div className="stat-card">
              <div className="stat-icon green">...</div>
              <div>
                <div className="stat-value">{stats.consulted}</div>
                <div className="stat-label">Consulted</div>
              </div>
            </div>
          </div>

          <div className="content-card">
            <div className="card-header">
              <h2>Waiting Patients</h2>
              <button className="btn btn-outline btn-sm" onClick={fetchQueue}>Refresh</button>
            </div>
            <div className="card-body">
              {queue.length === 0 ? (
                <div className="empty-state">
                  <p>No patients in the queue right now</p>
                </div>
              ) : (
                <table className="table" style={{ width: '100%', textAlign: 'left' }}>
                  <thead>
                    <tr>
                      <th>Token</th>
                      <th>Patient Details</th>
                      <th>Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    {queue.map(p => (
                      <tr key={p.id}>
                        <td>{p.token}</td>
                        <td>{p.name} ({p.age}/{p.gender})<br/><small>{p.complaint}</small></td>
                        <td>{p.status}</td>
                        <td>
                          {p.status === 'Waiting' ? (
                            <button className="btn btn-primary btn-sm" onClick={() => openPrescribeModal(p)}>
                              Prescribe
                            </button>
                          ) : (
                            <span style={{ color: 'green' }}>Completed</span>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          </div>
        </div>
      </main>

      {/* Prescribe Modal */}
      {isModalOpen && selectedPatient && (
        <div className="modal-overlay" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.5)', zIndex: 1000 }}>
          <div className="modal" style={{ background: '#fff', padding: '20px', borderRadius: '8px', width: '90%', maxWidth: '600px', maxHeight: '90vh', overflowY: 'auto' }}>
            <div className="modal-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
              <h3>Prescribe — {selectedPatient.name}</h3>
              <button onClick={() => setIsModalOpen(false)} style={{ background: 'none', border: 'none', fontSize: '1.5rem', cursor: 'pointer' }}>&times;</button>
            </div>
            
            <div className="modal-body">
              <div className="form-group">
                <label>Consultation Fee (₹)</label>
                <input type="number" value={prescData.fee} onChange={(e) => handlePrescChange('fee', e.target.value)} style={{ width: '100%', padding: '8px' }} />
              </div>

              <div style={{ display: 'flex', gap: '20px', margin: '20px 0' }}>
                <label><input type="checkbox" checked={prescData.requiresScan} onChange={(e) => handlePrescChange('requiresScan', e.target.checked)} /> Require Scan</label>
                <label><input type="checkbox" checked={prescData.requiresInjection} onChange={(e) => handlePrescChange('requiresInjection', e.target.checked)} /> Require Injection</label>
                <label><input type="checkbox" checked={prescData.requiresUpt} onChange={(e) => handlePrescChange('requiresUpt', e.target.checked)} /> UPT Card Required</label>
              </div>

              {prescData.requiresScan && (
                <div style={{ background: '#f8fafc', padding: '15px', borderRadius: '8px', marginBottom: '15px' }}>
                  <h4>Scan Details</h4>
                  <div style={{ display: 'flex', gap: '10px' }}>
                    <div style={{ flex: 2 }}><input type="text" placeholder="Scan Type" value={prescData.scanType} onChange={(e) => handlePrescChange('scanType', e.target.value)} style={{ width: '100%' }} /></div>
                    <div style={{ flex: 1 }}><input type="number" placeholder="Fee" value={prescData.scanFee} onChange={(e) => handlePrescChange('scanFee', e.target.value)} style={{ width: '100%' }} /></div>
                    <div style={{ flex: 2 }}><input type="text" placeholder="Notes" value={prescData.scanNotes} onChange={(e) => handlePrescChange('scanNotes', e.target.value)} style={{ width: '100%' }} /></div>
                  </div>
                </div>
              )}

              {prescData.requiresInjection && (
                <div style={{ background: '#f8fafc', padding: '15px', borderRadius: '8px', marginBottom: '15px' }}>
                  <h4>Injection Details</h4>
                  <div style={{ display: 'flex', gap: '10px' }}>
                    <div style={{ flex: 3 }}><input type="text" placeholder="Injection Details" value={prescData.injectionDetails} onChange={(e) => handlePrescChange('injectionDetails', e.target.value)} style={{ width: '100%' }} /></div>
                    <div style={{ flex: 1 }}><input type="number" placeholder="Cost" value={prescData.injectionCost} onChange={(e) => handlePrescChange('injectionCost', e.target.value)} style={{ width: '100%' }} /></div>
                  </div>
                </div>
              )}

              <div className="form-group">
                <label>Prescription Notes</label>
                <textarea rows="4" value={prescData.prescriptionNotes} onChange={(e) => handlePrescChange('prescriptionNotes', e.target.value)} style={{ width: '100%', padding: '8px' }}></textarea>
              </div>
            </div>
            
            <div className="modal-footer" style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px', marginTop: '20px', paddingTop: '20px', borderTop: '1px solid #e2e8f0' }}>
              <button className="btn btn-outline" onClick={() => setIsModalOpen(false)}>Close</button>
              <button className="btn btn-success" onClick={submitPrescription}>Approve & Send to Pharmacy</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default DoctorDashboard;
