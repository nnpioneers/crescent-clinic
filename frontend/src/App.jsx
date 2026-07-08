import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import Login from './pages/Login';

import ReceptionistDashboard from './pages/ReceptionistDashboard';
import DoctorDashboard from './pages/DoctorDashboard';
import PharmacyDashboard from './pages/PharmacyDashboard';
import ManagementDashboard from './pages/ManagementDashboard';

function App() {
  return (
    <AuthProvider>
      <Router>
        <Routes>
          <Route path="/" element={<Navigate to="/login" replace />} />
          <Route path="/login" element={<Login />} />
          <Route path="/receptionist" element={
            <ProtectedRoute allowedRoles={['receptionist', 'management']}>
              <ReceptionistDashboard />
            </ProtectedRoute>
          } />
          <Route path="/doctor" element={
            <ProtectedRoute allowedRoles={['doctor', 'management']}>
              <DoctorDashboard />
            </ProtectedRoute>
          } />
          <Route path="/pharmacy" element={
            <ProtectedRoute allowedRoles={['pharmacist', 'management']}>
              <PharmacyDashboard />
            </ProtectedRoute>
          } />
          <Route path="/management" element={
            <ProtectedRoute allowedRoles={['management']}>
              <ManagementDashboard />
            </ProtectedRoute>
          } />
        </Routes>
      </Router>
    </AuthProvider>
  );
}

export default App;
