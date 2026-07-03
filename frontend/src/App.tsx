import React, { useState, useEffect } from 'react';
import './App.css';

interface Hotel {
  id: number;
  hotel_group_id?: number;
  name: string;
  address?: string;
}

interface RoomType {
  id: number;
  hotel_id: number;
  name: string;
  description?: string;
  base_price: number | string;
  extra_bed_price?: number | string;
  max_occupancy?: number;
}

interface Room {
  id: number;
  hotel_id: number;
  room_type_id: number;
  room_type_name: string;
  room_number: string;
  floor: number;
  status: string;
}

interface User {
  id: number;
  username: string;
  email?: string;
  phone?: string;
  is_active: number;
  roles?: string;
  hotels?: string;
}

interface Feedback {
  type: 'success' | 'danger';
  message: string;
}

function App() {
  // Auth state
  const [token, setToken] = useState<string | null>(localStorage.getItem('hms_token'));
  const [user, setUser] = useState<any>(JSON.parse(localStorage.getItem('hms_user') || 'null'));
  const [allowedHotels, setAllowedHotels] = useState<Hotel[]>(JSON.parse(localStorage.getItem('hms_hotels') || '[]'));
  const [activeHotelId, setActiveHotelId] = useState<number>(
    parseInt(localStorage.getItem('hms_active_hotel_id') || '0')
  );

  // Login Form
  const [loginUsername, setLoginUsername] = useState('');
  const [loginPassword, setLoginPassword] = useState('');

  // App Tabs
  const [activeTab, setActiveTab] = useState<'dashboard' | 'hotels' | 'staff' | 'room-config' | 'rooms'>('dashboard');

  // Loaded DB data
  const [hotels, setHotels] = useState<Hotel[]>([]);
  const [groups, setGroups] = useState<any[]>([]);
  const [users, setUsers] = useState<User[]>([]);
  const [roles, setRoles] = useState<any[]>([]);
  const [roomTypes, setRoomTypes] = useState<RoomType[]>([]);
  const [rooms, setRooms] = useState<Room[]>([]);

  // Modals state
  const [selectedRoom, setSelectedRoom] = useState<Room | null>(null);
  const [newRoomStatus, setNewRoomStatus] = useState('');
  const [statusReason, setStatusReason] = useState('');

  const [selectedRateType, setSelectedRateType] = useState<RoomType | null>(null);
  const [satRate, setSatRate] = useState('');
  const [sunRate, setSunRate] = useState('');
  const [seasonalStart, setSeasonalStart] = useState('');
  const [seasonalEnd, setSeasonalEnd] = useState('');
  const [seasonalRate, setSeasonalRate] = useState('');
  const [seasonalDesc, setSeasonalDesc] = useState('');
  const [holidayDate, setHolidayDate] = useState('');
  const [holidayRate, setHolidayRate] = useState('');
  const [holidayDesc, setHolidayDesc] = useState('');

  // Tester Rate Tool
  const [testRateTypeId, setTestRateTypeId] = useState<number | null>(null);
  const [testRateDate, setTestRateDate] = useState(new Date().toISOString().split('T')[0]);
  const [calculatedRateResult, setCalculatedRateResult] = useState<number | null>(null);

  // User Manage Modals
  const [selectedUserForAccess, setSelectedUserForAccess] = useState<User | null>(null);
  const [accessRoles, setAccessRoles] = useState<number[]>([]);
  const [accessHotels, setAccessHotels] = useState<number[]>([]);

  // Input Forms
  const [newGroupName, setNewGroupName] = useState('');
  const [newHotelName, setNewHotelName] = useState('');
  const [newHotelAddress, setNewHotelAddress] = useState('');
  const [newHotelGroupId, setNewHotelGroupId] = useState('');

  const [newUsername, setNewUsername] = useState('');
  const [newUserPass, setNewUserPass] = useState('');
  const [newUserEmail, setNewUserEmail] = useState('');
  const [newUserPhone, setNewUserPhone] = useState('');

  const [newTypeName, setNewTypeName] = useState('');
  const [newTypePrice, setNewTypePrice] = useState('');
  const [newTypeExtra, setNewTypeExtra] = useState('');
  const [newTypeMax, setNewTypeMax] = useState('2');

  const [newRoomNumber, setNewRoomNumber] = useState('');
  const [newRoomTypeId, setNewRoomTypeId] = useState('');
  const [newRoomFloor, setNewRoomFloor] = useState('1');

  // Alert Banner
  const [feedback, setFeedback] = useState<Feedback | null>(null);

  // Show message utility
  const showFeedback = (type: 'success' | 'danger', message: string) => {
    setFeedback({ type, message });
    setTimeout(() => setFeedback(null), 5000);
  };

  // Helper Headers
  const getHeaders = () => {
    return {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    };
  };

  // 1. Fetch data depending on active settings
  const fetchAllInitialData = async () => {
    if (!token) return;
    try {
      // Fetch hotels allowed list
      const hRes = await fetch('/api/hotels', { headers: getHeaders() });
      const hData = await hRes.json();
      if (hData.success) {
        setHotels(hData.data);
        if (hData.data.length > 0 && activeHotelId === 0) {
          updateActiveHotel(hData.data[0].id);
        }
      }

      // Fetch groups
      const gRes = await fetch('/api/hotel-groups', { headers: getHeaders() });
      const gData = await gRes.json();
      if (gData.success) setGroups(gData.data);

      // Fetch users list
      const uRes = await fetch('/api/users', { headers: getHeaders() });
      const uData = await uRes.json();
      if (uData.success) setUsers(uData.data);

      // Fetch roles list
      const rRes = await fetch('/api/roles', { headers: getHeaders() });
      const rData = await rRes.json();
      if (rData.success) setRoles(rData.data);

    } catch (e) {
      showFeedback('danger', 'Failed to retrieve foundation data.');
    }
  };

  const fetchScopedData = async () => {
    if (!token || activeHotelId === 0) return;
    try {
      // Fetch room types
      const rtRes = await fetch(`/api/hotels/${activeHotelId}/room-types`, { headers: getHeaders() });
      const rtData = await rtRes.json();
      if (rtData.success) {
        setRoomTypes(rtData.data);
        if (rtData.data.length > 0) {
          setTestRateTypeId(rtData.data[0].id);
        }
      }

      // Fetch rooms list
      const rmRes = await fetch(`/api/hotels/${activeHotelId}/rooms`, { headers: getHeaders() });
      const rmData = await rmRes.json();
      if (rmData.success) setRooms(rmData.data);

    } catch (e) {
      showFeedback('danger', 'Failed to retrieve hotel-specific room metadata.');
    }
  };

  useEffect(() => {
    if (token) {
      fetchAllInitialData();
    }
  }, [token]);

  useEffect(() => {
    if (token && activeHotelId > 0) {
      fetchScopedData();
    }
  }, [token, activeHotelId, activeTab]);

  const updateActiveHotel = (id: number) => {
    setActiveHotelId(id);
    localStorage.setItem('hms_active_hotel_id', id.toString());
  };

  // Auth Operations
  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await fetch('/api/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username: loginUsername, password: loginPassword })
      });
      const data = await res.json();
      if (data.success) {
        const { token: jwt, user: u, hotels: h } = data.data;
        localStorage.setItem('hms_token', jwt);
        localStorage.setItem('hms_user', JSON.stringify(u));
        localStorage.setItem('hms_hotels', JSON.stringify(h));
        setToken(jwt);
        setUser(u);
        setAllowedHotels(h);
        if (h.length > 0) {
          updateActiveHotel(h[0].id);
        }
        showFeedback('success', 'Logged in successfully.');
      } else {
        showFeedback('danger', data.message || 'Login failed.');
      }
    } catch (err) {
      showFeedback('danger', 'Network error during login.');
    }
  };

  const handleLogout = () => {
    localStorage.removeItem('hms_token');
    localStorage.removeItem('hms_user');
    localStorage.removeItem('hms_hotels');
    localStorage.removeItem('hms_active_hotel_id');
    setToken(null);
    setUser(null);
    setAllowedHotels([]);
    setActiveHotelId(0);
  };

  // CRUD Operations
  const handleCreateGroup = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await fetch('/api/hotel-groups', {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({ name: newGroupName })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', 'Hotel group registered successfully.');
        setNewGroupName('');
        fetchAllInitialData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to register group.');
    }
  };

  const handleCreateHotel = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await fetch('/api/hotels', {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          name: newHotelName,
          address: newHotelAddress,
          hotel_group_id: parseInt(newHotelGroupId)
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', 'Hotel registered successfully.');
        setNewHotelName('');
        setNewHotelAddress('');
        fetchAllInitialData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to register hotel.');
    }
  };

  const handleCreateStaff = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await fetch('/api/users', {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          username: newUsername,
          password: newUserPass,
          email: newUserEmail,
          phone: newUserPhone
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', 'Staff account created successfully.');
        setNewUsername('');
        setNewUserPass('');
        setNewUserEmail('');
        setNewUserPhone('');
        fetchAllInitialData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to create staff.');
    }
  };

  const handleSaveAccess = async () => {
    if (!selectedUserForAccess) return;
    try {
      const res = await fetch(`/api/users/${selectedUserForAccess.id}/access`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          role_ids: accessRoles,
          hotel_ids: accessHotels
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', 'Staff access permissions updated.');
        setSelectedUserForAccess(null);
        fetchAllInitialData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to update access scopes.');
    }
  };

  const handleCreateRoomType = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/room-types`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          name: newTypeName,
          base_price: parseFloat(newTypePrice),
          extra_bed_price: parseFloat(newTypeExtra || '0'),
          max_occupancy: parseInt(newTypeMax)
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', 'Room type created successfully.');
        setNewTypeName('');
        setNewTypePrice('');
        setNewTypeExtra('');
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to create room type.');
    }
  };

  const handleCreateRoom = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/rooms`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          room_number: newRoomNumber,
          room_type_id: parseInt(newRoomTypeId),
          floor: parseInt(newRoomFloor)
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', 'Room registered successfully.');
        setNewRoomNumber('');
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to register room.');
    }
  };

  // Status Shift Operations
  const handleUpdateStatus = async () => {
    if (!selectedRoom) return;
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/rooms/${selectedRoom.id}/status`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          status: newRoomStatus,
          reason: statusReason
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', `Room ${selectedRoom.room_number} status updated to ${newRoomStatus}.`);
        setSelectedRoom(null);
        setStatusReason('');
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to update room status.');
    }
  };

  // Pricing Rule Operations
  const handleConfigureRates = async () => {
    if (!selectedRateType) return;
    const body: any = {};

    // Configure weekends if input is set
    if (satRate || sunRate) {
      body.weekend_rates = {};
      if (satRate) body.weekend_rates[6] = parseFloat(satRate);
      if (sunRate) body.weekend_rates[0] = parseFloat(sunRate);
    }

    // Configure seasonal if input is set
    if (seasonalStart && seasonalEnd && seasonalRate) {
      body.seasonal_rates = [
        {
          start_date: seasonalStart,
          end_date: seasonalEnd,
          rate: parseFloat(seasonalRate),
          description: seasonalDesc
        }
      ];
    }

    // Configure holiday if input is set
    if (holidayDate && holidayRate) {
      body.holiday_rates = [
        {
          date: holidayDate,
          rate: parseFloat(holidayRate),
          description: holidayDesc
        }
      ];
    }

    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/room-types/${selectedRateType.id}/rates`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify(body)
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', 'Custom pricing overrides configured successfully.');
        setSelectedRateType(null);
        setSatRate('');
        setSunRate('');
        setSeasonalStart('');
        setSeasonalEnd('');
        setSeasonalRate('');
        setSeasonalDesc('');
        setHolidayDate('');
        setHolidayRate('');
        setHolidayDesc('');
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to configure pricing rules.');
    }
  };

  // Test Pricing Engine Calculator
  const handleCalculateTestRate = async () => {
    if (!testRateTypeId) return;
    try {
      const res = await fetch(
        `/api/hotels/${activeHotelId}/room-types/${testRateTypeId}/calculate-rate?date=${testRateDate}`,
        { headers: getHeaders() }
      );
      const data = await res.json();
      if (data.success) {
        setCalculatedRateResult(data.data.calculated_rate);
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Rate engine calculation call failed.');
    }
  };

  // Check login state
  if (!token) {
    return (
      <div className="login-screen">
        <div className="glass-panel login-card">
          <div className="login-header">
            <div className="login-logo">HMS <span className="brand-accent">Tenancy</span></div>
            <p className="login-title">Hospitality Core Administration Panel</p>
          </div>
          {feedback && <div className={`alert alert-${feedback.type}`}>{feedback.message}</div>}
          <form onSubmit={handleLogin}>
            <div className="form-group" style={{ marginBottom: '16px' }}>
              <label className="form-label">Username</label>
              <input
                type="text"
                className="form-input"
                placeholder="Enter staff username..."
                value={loginUsername}
                onChange={(e) => setLoginUsername(e.target.value)}
                required
              />
            </div>
            <div className="form-group" style={{ marginBottom: '24px' }}>
              <label className="form-label">Password</label>
              <input
                type="password"
                className="form-input"
                placeholder="Enter password..."
                value={loginPassword}
                onChange={(e) => setLoginPassword(e.target.value)}
                required
              />
            </div>
            <button type="submit" className="btn" style={{ width: '100%' }}>Sign In</button>
          </form>
        </div>
      </div>
    );
  }

  return (
    <div className="app-container">
      {/* Sidebar Navigation */}
      <aside className="sidebar">
        <div className="brand">HMS <span className="brand-accent">Core</span></div>
        <ul className="nav-links">
          <li className="nav-item">
            <button
              onClick={() => setActiveTab('dashboard')}
              className={`nav-button ${activeTab === 'dashboard' ? 'active' : ''}`}
            >
              Dashboard Grid
            </button>
          </li>
          <li className="nav-item">
            <button
              onClick={() => setActiveTab('hotels')}
              className={`nav-button ${activeTab === 'hotels' ? 'active' : ''}`}
            >
              Hotels & Groups
            </button>
          </li>
          <li className="nav-item">
            <button
              onClick={() => setActiveTab('staff')}
              className={`nav-button ${activeTab === 'staff' ? 'active' : ''}`}
            >
              Staff & RBAC
            </button>
          </li>
          <li className="nav-item">
            <button
              onClick={() => setActiveTab('room-config')}
              className={`nav-button ${activeTab === 'room-config' ? 'active' : ''}`}
            >
              Pricing & Types
            </button>
          </li>
          <li className="nav-item">
            <button
              onClick={() => setActiveTab('rooms')}
              className={`nav-button ${activeTab === 'rooms' ? 'active' : ''}`}
            >
              Room Inventory
            </button>
          </li>
        </ul>
      </aside>

      {/* Main Panel Content */}
      <main className="main-content">
        {/* Top Header Context selector */}
        <header className="top-bar">
          <div className="context-selector">
            <span className="context-label">Active Tenant</span>
            <select
              className="select-dropdown"
              value={activeHotelId}
              onChange={(e) => updateActiveHotel(parseInt(e.target.value))}
            >
              <option value="0">-- Select Hotel --</option>
              {allowedHotels.map((h) => (
                <option key={h.id} value={h.id}>
                  {h.name}
                </option>
              ))}
            </select>
          </div>
          <div className="user-profile">
            <span className="user-name">Welcome, {user?.username}</span>
            <button onClick={handleLogout} className="logout-button">
              Logout
            </button>
          </div>
        </header>

        {/* Dynamic Page content */}
        <div className="page-body">
          {feedback && <div className={`alert alert-${feedback.type}`}>{feedback.message}</div>}

          {/* 1. ROOMS DASHBOARD GRID */}
          {activeTab === 'dashboard' && (
            <div className="glass-panel">
              <h2 className="page-title">Housekeeping Rooms Dashboard</h2>
              <p className="page-subtitle">Real-time room occupancy, cleaning, and maintenance state trackers.</p>

              {rooms.length === 0 ? (
                <div style={{ textAlign: 'center', padding: '40px 0', color: '#9ca3af' }}>
                  No rooms configured for this hotel yet. Add rooms in Room Inventory.
                </div>
              ) : (
                <div className="rooms-grid">
                  {rooms.map((r) => (
                    <div
                      key={r.id}
                      className={`room-card status-${r.status}`}
                      onClick={() => {
                        setSelectedRoom(r);
                        setNewRoomStatus(r.status);
                      }}
                    >
                      <div className="room-status-indicator" />
                      <div className="room-number">{r.room_number}</div>
                      <div className="room-type">{r.room_type_name}</div>
                      <div className="room-status-badge">{r.status}</div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {/* 2. HOTELS AND GROUPS ADMIN */}
          {activeTab === 'hotels' && (
            <div>
              <div className="glass-panel">
                <h2 className="page-title">Register Hotel Group</h2>
                <form onSubmit={handleCreateGroup}>
                  <div className="form-grid">
                    <div className="form-group">
                      <label className="form-label">Group Name</label>
                      <input
                        type="text"
                        className="form-input"
                        placeholder="e.g. Royal Palace Group"
                        value={newGroupName}
                        onChange={(e) => setNewGroupName(e.target.value)}
                        required
                      />
                    </div>
                  </div>
                  <button type="submit" className="btn">Create Group</button>
                </form>
              </div>

              <div className="glass-panel">
                <h2 className="page-title">Register Individual Hotel</h2>
                <form onSubmit={handleCreateHotel}>
                  <div className="form-grid">
                    <div className="form-group">
                      <label className="form-label">Hotel Name</label>
                      <input
                        type="text"
                        className="form-input"
                        placeholder="e.g. Grand Shivalik Resort"
                        value={newHotelName}
                        onChange={(e) => setNewHotelName(e.target.value)}
                        required
                      />
                    </div>
                    <div className="form-group">
                      <label className="form-label">Hotel Group Scope</label>
                      <select
                        className="select-dropdown"
                        style={{ height: '42px' }}
                        value={newHotelGroupId}
                        onChange={(e) => setNewHotelGroupId(e.target.value)}
                        required
                      >
                        <option value="">-- Select Group --</option>
                        {groups.map((g) => (
                          <option key={g.id} value={g.id}>{g.name}</option>
                        ))}
                      </select>
                    </div>
                    <div className="form-group">
                      <label className="form-label">Address</label>
                      <input
                        type="text"
                        className="form-input"
                        placeholder="Shimla, HP, India"
                        value={newHotelAddress}
                        onChange={(e) => setNewHotelAddress(e.target.value)}
                      />
                    </div>
                  </div>
                  <button type="submit" className="btn">Add Hotel</button>
                </form>
              </div>

              <div className="glass-panel">
                <h2 className="page-title">Registered Properties</h2>
                <div className="table-wrapper">
                  <table className="table">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Address</th>
                      </tr>
                    </thead>
                    <tbody>
                      {hotels.map((h) => (
                        <tr key={h.id}>
                          <td>{h.id}</td>
                          <td>{h.name}</td>
                          <td>{h.address || 'N/A'}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}

          {/* 3. STAFF ACCESS CONTROLS AND RBAC */}
          {activeTab === 'staff' && (
            <div>
              <div className="glass-panel">
                <h2 className="page-title">Add Staff Member</h2>
                <form onSubmit={handleCreateStaff}>
                  <div className="form-grid">
                    <div className="form-group">
                      <label className="form-label">Username</label>
                      <input
                        type="text"
                        className="form-input"
                        value={newUsername}
                        onChange={(e) => setNewUsername(e.target.value)}
                        required
                      />
                    </div>
                    <div className="form-group">
                      <label className="form-label">Password</label>
                      <input
                        type="password"
                        className="form-input"
                        value={newUserPass}
                        onChange={(e) => setNewUserPass(e.target.value)}
                        required
                      />
                    </div>
                    <div className="form-group">
                      <label className="form-label">Email</label>
                      <input
                        type="email"
                        className="form-input"
                        value={newUserEmail}
                        onChange={(e) => setNewUserEmail(e.target.value)}
                      />
                    </div>
                    <div className="form-group">
                      <label className="form-label">Phone</label>
                      <input
                        type="text"
                        className="form-input"
                        value={newUserPhone}
                        onChange={(e) => setNewUserPhone(e.target.value)}
                      />
                    </div>
                  </div>
                  <button type="submit" className="btn">Invite Staff</button>
                </form>
              </div>

              <div className="glass-panel">
                <h2 className="page-title">Staff Members & Permissions Mappings</h2>
                <div className="table-wrapper">
                  <table className="table">
                    <thead>
                      <tr>
                        <th>Staff Username</th>
                        <th>Email</th>
                        <th>Assigned Roles</th>
                        <th>Hotels Scope</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      {users.map((u) => (
                        <tr key={u.id}>
                          <td>{u.username}</td>
                          <td>{u.email || 'N/A'}</td>
                          <td>
                            <span className="badge badge-purple">{u.roles || 'No Roles'}</span>
                          </td>
                          <td>
                            <span className="badge badge-green">{u.hotels || 'No Access'}</span>
                          </td>
                          <td>
                            <button
                              onClick={() => {
                                setSelectedUserForAccess(u);
                                setAccessRoles([]);
                                setAccessHotels([]);
                              }}
                              className="btn btn-secondary"
                              style={{ padding: '6px 12px', fontSize: '12px' }}
                            >
                              Edit Access Mappings
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}

          {/* 4. ROOM TYPES CONFIGURATION AND RATES */}
          {activeTab === 'room-config' && (
            <div>
              <div className="glass-panel">
                <h2 className="page-title">Add Room Type Category</h2>
                <form onSubmit={handleCreateRoomType}>
                  <div className="form-grid">
                    <div className="form-group">
                      <label className="form-label">Room Type Name</label>
                      <input
                        type="text"
                        className="form-input"
                        placeholder="e.g. Deluxe Suite"
                        value={newTypeName}
                        onChange={(e) => setNewTypeName(e.target.value)}
                        required
                      />
                    </div>
                    <div className="form-group">
                      <label className="form-label">Base Rate (INR)</label>
                      <input
                        type="number"
                        className="form-input"
                        placeholder="3500.00"
                        value={newTypePrice}
                        onChange={(e) => setNewTypePrice(e.target.value)}
                        required
                      />
                    </div>
                    <div className="form-group">
                      <label className="form-label">Extra Bed Charge (INR)</label>
                      <input
                        type="number"
                        className="form-input"
                        placeholder="500.00"
                        value={newTypeExtra}
                        onChange={(e) => setNewTypeExtra(e.target.value)}
                      />
                    </div>
                    <div className="form-group">
                      <label className="form-label">Max Occupancy</label>
                      <input
                        type="number"
                        className="form-input"
                        value={newTypeMax}
                        onChange={(e) => setNewTypeMax(e.target.value)}
                        required
                      />
                    </div>
                  </div>
                  <button type="submit" className="btn">Add Room Type</button>
                </form>
              </div>

              <div className="glass-panel">
                <h2 className="page-title">Room Categories & Overrides</h2>
                <div className="table-wrapper">
                  <table className="table">
                    <thead>
                      <tr>
                        <th>Category Name</th>
                        <th>Base Rate (INR)</th>
                        <th>Extra Bed Rate</th>
                        <th>Max Occupancy</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      {roomTypes.map((rt) => (
                        <tr key={rt.id}>
                          <td>{rt.name}</td>
                          <td>₹{rt.base_price}</td>
                          <td>₹{rt.extra_bed_price || '0.00'}</td>
                          <td>{rt.max_occupancy || 2} Guests</td>
                          <td>
                            <button
                              onClick={() => setSelectedRateType(rt)}
                              className="btn"
                              style={{ padding: '6px 12px', fontSize: '12px' }}
                            >
                              Configure Rates (Weekend / Seasons)
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>

              {/* Price test simulator */}
              <div className="glass-panel">
                <h2 className="page-title">Pricing Engine Simulator</h2>
                <p className="page-subtitle">Verify dynamic rate resolution rules (Holiday &rarr; Seasonal &rarr; Weekend &rarr; Base priority).</p>
                <div className="form-grid">
                  <div className="form-group">
                    <label className="form-label">Room Type</label>
                    <select
                      className="select-dropdown"
                      style={{ height: '42px' }}
                      value={testRateTypeId || ''}
                      onChange={(e) => setTestRateTypeId(parseInt(e.target.value))}
                    >
                      <option value="">-- Select Room Type --</option>
                      {roomTypes.map((rt) => (
                        <option key={rt.id} value={rt.id}>{rt.name}</option>
                      ))}
                    </select>
                  </div>
                  <div className="form-group">
                    <label className="form-label">Target Date</label>
                    <input
                      type="date"
                      className="form-input"
                      value={testRateDate}
                      onChange={(e) => setTestRateDate(e.target.value)}
                    />
                  </div>
                </div>
                <button onClick={handleCalculateTestRate} className="btn btn-secondary">Calculate resolved rate</button>

                {calculatedRateResult !== null && (
                  <div style={{ marginTop: '20px', fontSize: '18px', fontWeight: 'bold' }}>
                    Calculated Resolved Rate for {testRateDate}: <span style={{ color: 'var(--primary-hover)' }}>₹{calculatedRateResult}</span>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* 5. ROOM INVENTORY */}
          {activeTab === 'rooms' && (
            <div>
              <div className="glass-panel">
                <h2 className="page-title">Add Room to Inventory</h2>
                <form onSubmit={handleCreateRoom}>
                  <div className="form-grid">
                    <div className="form-group">
                      <label className="form-label">Room Number</label>
                      <input
                        type="text"
                        className="form-input"
                        placeholder="e.g. 101, 204"
                        value={newRoomNumber}
                        onChange={(e) => setNewRoomNumber(e.target.value)}
                        required
                      />
                    </div>
                    <div className="form-group">
                      <label className="form-label">Room Category type</label>
                      <select
                        className="select-dropdown"
                        style={{ height: '42px' }}
                        value={newRoomTypeId}
                        onChange={(e) => setNewRoomTypeId(e.target.value)}
                        required
                      >
                        <option value="">-- Select Category --</option>
                        {roomTypes.map((rt) => (
                          <option key={rt.id} value={rt.id}>{rt.name}</option>
                        ))}
                      </select>
                    </div>
                    <div className="form-group">
                      <label className="form-label">Floor Number</label>
                      <input
                        type="number"
                        className="form-input"
                        value={newRoomFloor}
                        onChange={(e) => setNewRoomFloor(e.target.value)}
                        required
                      />
                    </div>
                  </div>
                  <button type="submit" className="btn">Register Room</button>
                </form>
              </div>

              <div className="glass-panel">
                <h2 className="page-title">Active Rooms List</h2>
                <div className="table-wrapper">
                  <table className="table">
                    <thead>
                      <tr>
                        <th>Room Number</th>
                        <th>Floor</th>
                        <th>Category</th>
                        <th>Housekeeping Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      {rooms.map((r) => (
                        <tr key={r.id}>
                          <td><strong>{r.room_number}</strong></td>
                          <td>Floor {r.floor}</td>
                          <td>{r.room_type_name}</td>
                          <td>
                            <span className={`room-status-badge status-${r.status}`} style={{ margin: 0 }}>
                              {r.status}
                            </span>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          )}
        </div>
      </main>

      {/* MODAL 1: STATUS SHIFT */}
      {selectedRoom && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card">
            <div className="modal-header">
              <h3 className="modal-title">Transition Status - Room {selectedRoom.room_number}</h3>
              <button onClick={() => setSelectedRoom(null)} className="modal-close">×</button>
            </div>
            <div className="form-group" style={{ marginBottom: '16px' }}>
              <label className="form-label">New Status</label>
              <select
                className="select-dropdown"
                value={newRoomStatus}
                onChange={(e) => setNewRoomStatus(e.target.value)}
              >
                <option value="Available">Available</option>
                <option value="Cleaning">Cleaning</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Blocked">Blocked</option>
                <option value="Reserved">Reserved</option>
                <option value="Occupied">Occupied</option>
              </select>
            </div>
            {['Maintenance', 'Blocked'].includes(newRoomStatus) && (
              <div className="form-group" style={{ marginBottom: '16px' }}>
                <label className="form-label">Reason for transition (Required)</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="e.g. Broken AC unit, annual whitewash painting..."
                  value={statusReason}
                  onChange={(e) => setStatusReason(e.target.value)}
                  required
                />
              </div>
            )}
            <div className="modal-footer">
              <button onClick={() => setSelectedRoom(null)} className="btn btn-secondary">Cancel</button>
              <button onClick={handleUpdateStatus} className="btn">Apply Status</button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL 2: CONFIGURE DYNAMIC PRICING FOR CATEGORY */}
      {selectedRateType && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card" style={{ width: '600px', maxHeight: '90vh', overflowY: 'auto' }}>
            <div className="modal-header">
              <h3 className="modal-title">Configure Pricing Overrides - {selectedRateType.name}</h3>
              <button onClick={() => setSelectedRateType(null)} className="modal-close">×</button>
            </div>
            
            {/* Weekend */}
            <div style={{ marginBottom: '20px', borderBottom: '1px solid rgba(255,255,255,0.08)', paddingBottom: '12px' }}>
              <h4 style={{ margin: '0 0 10px 0', color: '#fff' }}>Weekend Price Overrides</h4>
              <div className="form-grid">
                <div className="form-group">
                  <label className="form-label">Saturday Rate (INR)</label>
                  <input
                    type="number"
                    className="form-input"
                    placeholder="e.g. 4500"
                    value={satRate}
                    onChange={(e) => setSatRate(e.target.value)}
                  />
                </div>
                <div className="form-group">
                  <label className="form-label">Sunday Rate (INR)</label>
                  <input
                    type="number"
                    className="form-input"
                    placeholder="e.g. 4500"
                    value={sunRate}
                    onChange={(e) => setSunRate(e.target.value)}
                  />
                </div>
              </div>
            </div>

            {/* Seasonal */}
            <div style={{ marginBottom: '20px', borderBottom: '1px solid rgba(255,255,255,0.08)', paddingBottom: '12px' }}>
              <h4 style={{ margin: '0 0 10px 0', color: '#fff' }}>Seasonal Rate Rule</h4>
              <div className="form-grid">
                <div className="form-group">
                  <label className="form-label">Start Date</label>
                  <input
                    type="date"
                    className="form-input"
                    value={seasonalStart}
                    onChange={(e) => setSeasonalStart(e.target.value)}
                  />
                </div>
                <div className="form-group">
                  <label className="form-label">End Date</label>
                  <input
                    type="date"
                    className="form-input"
                    value={seasonalEnd}
                    onChange={(e) => setSeasonalEnd(e.target.value)}
                  />
                </div>
              </div>
              <div className="form-grid" style={{ marginTop: '10px' }}>
                <div className="form-group">
                  <label className="form-label">Seasonal Rate (INR)</label>
                  <input
                    type="number"
                    className="form-input"
                    placeholder="e.g. 5000"
                    value={seasonalRate}
                    onChange={(e) => setSeasonalRate(e.target.value)}
                  />
                </div>
                <div className="form-group">
                  <label className="form-label">Season Description</label>
                  <input
                    type="text"
                    className="form-input"
                    placeholder="e.g. Monsoon Special"
                    value={seasonalDesc}
                    onChange={(e) => setSeasonalDesc(e.target.value)}
                  />
                </div>
              </div>
            </div>

            {/* Holiday */}
            <div style={{ marginBottom: '10px' }}>
              <h4 style={{ margin: '0 0 10px 0', color: '#fff' }}>Holiday Rate Override</h4>
              <div className="form-grid">
                <div className="form-group">
                  <label className="form-label">Holiday Date</label>
                  <input
                    type="date"
                    className="form-input"
                    value={holidayDate}
                    onChange={(e) => setHolidayDate(e.target.value)}
                  />
                </div>
                <div className="form-group">
                  <label className="form-label">Holiday Rate (INR)</label>
                  <input
                    type="number"
                    className="form-input"
                    placeholder="e.g. 6500"
                    value={holidayRate}
                    onChange={(e) => setHolidayRate(e.target.value)}
                  />
                </div>
                <div className="form-group">
                  <label className="form-label">Holiday Name</label>
                  <input
                    type="text"
                    className="form-input"
                    placeholder="e.g. New Years Eve"
                    value={holidayDesc}
                    onChange={(e) => setHolidayDesc(e.target.value)}
                  />
                </div>
              </div>
            </div>

            <div className="modal-footer">
              <button onClick={() => setSelectedRateType(null)} className="btn btn-secondary">Cancel</button>
              <button onClick={handleConfigureRates} className="btn">Save Rate Overrides</button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL 3: STAFF PERMISSIONS OVERRIDES */}
      {selectedUserForAccess && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card">
            <div className="modal-header">
              <h3 className="modal-title">Configure Permissions - {selectedUserForAccess.username}</h3>
              <button onClick={() => setSelectedUserForAccess(null)} className="modal-close">×</button>
            </div>
            
            <div className="form-group" style={{ marginBottom: '16px' }}>
              <label className="form-label">Select Roles (Multi-select)</label>
              <div style={{ display: 'flex', flexDirection: 'column', gap: '8px', background: 'rgba(0,0,0,0.2)', padding: '10px', borderRadius: '6px' }}>
                {roles.map((r) => (
                  <label key={r.id} style={{ display: 'flex', alignItems: 'center', gap: '10px', cursor: 'pointer' }}>
                    <input
                      type="checkbox"
                      checked={accessRoles.includes(r.id)}
                      onChange={(e) => {
                        if (e.target.checked) {
                          setAccessRoles([...accessRoles, r.id]);
                        } else {
                          setAccessRoles(accessRoles.filter((id) => id !== r.id));
                        }
                      }}
                    />
                    {r.name} ({r.description || 'No description'})
                  </label>
                ))}
              </div>
            </div>

            <div className="form-group" style={{ marginBottom: '16px' }}>
              <label className="form-label">Granted Hotels Access (Multi-select)</label>
              <div style={{ display: 'flex', flexDirection: 'column', gap: '8px', background: 'rgba(0,0,0,0.2)', padding: '10px', borderRadius: '6px' }}>
                {hotels.map((h) => (
                  <label key={h.id} style={{ display: 'flex', alignItems: 'center', gap: '10px', cursor: 'pointer' }}>
                    <input
                      type="checkbox"
                      checked={accessHotels.includes(h.id)}
                      onChange={(e) => {
                        if (e.target.checked) {
                          setAccessHotels([...accessHotels, h.id]);
                        } else {
                          setAccessHotels(accessHotels.filter((id) => id !== h.id));
                        }
                      }}
                    />
                    {h.name}
                  </label>
                ))}
              </div>
            </div>

            <div className="modal-footer">
              <button onClick={() => setSelectedUserForAccess(null)} className="btn btn-secondary">Cancel</button>
              <button onClick={handleSaveAccess} className="btn">Save Access Controls</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default App;
