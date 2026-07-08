import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useAuth } from '../context/AuthContext';
import { useNavigate } from 'react-router-dom';

const PharmacyDashboard = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('Prescriptions');
  
  // Data States
  const [prescriptions, setPrescriptions] = useState([]);
  const [directSales, setDirectSales] = useState([]);
  const [stats, setStats] = useState({ revenue: '0.00' });
  const [isMedicineModalOpen, setIsMedicineModalOpen] = useState(false);

  useEffect(() => {
    if (activeTab === 'Prescriptions') fetchPrescriptions();
    else if (activeTab === 'DirectSale') fetchDirectSales();
  }, [activeTab]);

  const fetchPrescriptions = async () => {
    try {
      const res = await axios.get('/api/pharmacy/prescriptions');
      if (res.data) setPrescriptions(res.data);
    } catch (err) {
      console.error('Failed to load prescriptions');
    }
  };

  const fetchDirectSales = async () => {
    try {
      const res = await axios.get('/api/pharmacy/direct-sales');
      if (res.data) setDirectSales(res.data);
    } catch (err) {
      console.error('Failed to load direct sales');
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
            <small>Pharmacy</small>
          </div>
        </div>
        <nav className="sidebar-nav">
          <button className={`nav-item ${activeTab === 'Prescriptions' ? 'active' : ''}`} onClick={() => setActiveTab('Prescriptions')}>
            All Patients
          </button>
          <button className={`nav-item ${activeTab === 'DirectSale' ? 'active' : ''}`} onClick={() => setActiveTab('DirectSale')}>
            Direct Medicine
          </button>
          <hr style={{border: 'none', borderTop: '1px solid var(--border)', margin: '10px 0'}} />
          <button className={`nav-item ${activeTab === 'Inventory' ? 'active' : ''}`} onClick={() => setActiveTab('Inventory')}>
            Pharmacy Inventory
          </button>
          <button className={`nav-item ${activeTab === 'Agency' ? 'active' : ''}`} onClick={() => setActiveTab('Agency')}>
            Agency Inventory
          </button>
        </nav>
        <div className="sidebar-footer">
          <div className="user-info">
            <div className="user-avatar">P</div>
            <div>
              <div className="name">{user?.display_name || 'Pharmacist'}</div>
              <div className="role-label">Pharmacist</div>
            </div>
          </div>
          <button onClick={() => { logout(); navigate('/login'); }} className="btn btn-outline btn-sm btn-full">
            Logout
          </button>
        </div>
      </aside>

      <main className="main-content">
        {activeTab === 'Prescriptions' && (
          <div className="section active">
            <div className="page-header">
              <h1>Pharmacy</h1>
              <p>Manage prescriptions, add medicines, generate bills</p>
            </div>

            <div className="stats-row">
              <div className="stat-card">
                <div className="stat-icon green">...</div>
                <div>
                  <div className="stat-value">₹{stats.revenue}</div>
                  <div className="stat-label">Total Revenue (Profit)</div>
                </div>
              </div>
            </div>

            <div className="content-card">
              <div className="card-header">
                <h2>Prescription List</h2>
                <button className="btn btn-outline btn-sm" onClick={fetchPrescriptions}>Refresh</button>
              </div>
              <div className="card-body-np">
                {prescriptions.length === 0 ? (
                  <div className="empty-state">
                    <p>No prescriptions found</p>
                  </div>
                ) : (
                  <table className="data-table" style={{ width: '100%', textAlign: 'left' }}>
                    <thead>
                      <tr>
                        <th>Token</th>
                        <th>Patient ID</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {prescriptions.map(p => (
                        <tr key={p.id}>
                          <td>{p.token}</td>
                          <td>{p.patient_id}</td>
                          <td>{p.name}</td>
                          <td>{p.doctor_name}</td>
                          <td>{p.status}</td>
                          <td>
                            <button className="btn btn-primary btn-sm" onClick={() => setIsMedicineModalOpen(true)}>Process</button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                )}
              </div>
            </div>
          </div>
        )}

        {activeTab === 'DirectSale' && (
          <div className="section active">
            <div className="page-header" style={{ display: 'flex', justifyContent: 'space-between' }}>
              <div>
                <h1>Direct Medicine Sale</h1>
                <p>Walk-in customer sales (no consultation)</p>
              </div>
              <button className="btn btn-primary" onClick={() => setIsMedicineModalOpen(true)}>New Direct Sale</button>
            </div>
            
            <div className="content-card">
              <div className="card-header">
                <h2>Recent Direct Sales</h2>
              </div>
              <div className="card-body-np">
                 {directSales.length === 0 ? (
                  <div className="empty-state">
                    <p>No direct sales found</p>
                  </div>
                ) : (
                  <table className="data-table" style={{ width: '100%', textAlign: 'left' }}>
                    <thead>
                      <tr>
                        <th>Customer Name</th>
                        <th>Mobile</th>
                        <th>Bill Amount</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      {directSales.map(ds => (
                        <tr key={ds.id}>
                          <td>{ds.name}</td>
                          <td>{ds.phone}</td>
                          <td>₹{ds.total}</td>
                          <td>{ds.status}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                )}
              </div>
            </div>
          </div>
        )}

        {activeTab === 'Inventory' && (
          <div className="section active">
             <div className="page-header">
                <h1>Pharmacy Inventory</h1>
                <p>Manage medicines, batches and stock</p>
            </div>
            <div className="content-card">
               <div className="card-body">
                 <p>Inventory management module will be loaded here.</p>
               </div>
            </div>
          </div>
        )}

        {activeTab === 'Agency' && (
          <div className="section active">
             <div className="page-header">
                <h1>Agency Inventory</h1>
                <p>Manage supplier orders and bulk stock</p>
            </div>
            <div className="content-card">
               <div className="card-body">
                 <p>Agency management module will be loaded here.</p>
               </div>
            </div>
          </div>
        )}
      </main>

      {/* Simplified Medicine Modal */}
      {isMedicineModalOpen && (
        <div className="modal-overlay" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(0,0,0,0.5)', zIndex: 1000 }}>
           <div className="modal" style={{ background: '#fff', padding: '20px', borderRadius: '8px', width: '90%', maxWidth: '720px', maxHeight: '90vh', overflowY: 'auto' }}>
              <div className="modal-header" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h3>Process Bill / Add Medicines</h3>
                <button onClick={() => setIsMedicineModalOpen(false)} style={{ background: 'none', border: 'none', fontSize: '1.5rem', cursor: 'pointer' }}>&times;</button>
              </div>
              <div className="modal-body">
                <p>Medicine entry form goes here (Search, Add Row, Calculate Total)</p>
              </div>
              <div className="modal-footer" style={{ display: 'flex', justifyContent: 'flex-end', gap: '10px', marginTop: '20px', paddingTop: '20px', borderTop: '1px solid #e2e8f0' }}>
                <button className="btn btn-outline" onClick={() => setIsMedicineModalOpen(false)}>Cancel</button>
                <button className="btn btn-success">Save Bill</button>
              </div>
           </div>
        </div>
      )}
    </div>
  );
};

export default PharmacyDashboard;
