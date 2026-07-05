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
  const [activeTab, setActiveTab] = useState<'dashboard' | 'hotels' | 'staff' | 'room-config' | 'rooms' | 'guests' | 'reservations' | 'stays'>('dashboard');

  // Loaded DB data
  const [hotels, setHotels] = useState<Hotel[]>([]);
  const [groups, setGroups] = useState<any[]>([]);
  const [users, setUsers] = useState<User[]>([]);
  const [roles, setRoles] = useState<any[]>([]);
  const [roomTypes, setRoomTypes] = useState<RoomType[]>([]);
  const [rooms, setRooms] = useState<Room[]>([]);

  // Phase 3 State
  const [guests, setGuests] = useState<any[]>([]);
  const [reservations, setReservations] = useState<any[]>([]);
  const [stays, setStays] = useState<any[]>([]);
  
  // Guest state
  const [selectedGuest, setSelectedGuest] = useState<any | null>(null);
  const [guestDocs, setGuestDocs] = useState<any[]>([]);
  const [decryptedDocNum, setDecryptedDocNum] = useState<string | null>(null);
  const [newGuestFirst, setNewGuestFirst] = useState('');
  const [newGuestLast, setNewGuestLast] = useState('');
  const [newGuestEmail, setNewGuestEmail] = useState('');
  const [newGuestPhone, setNewGuestPhone] = useState('');
  
  // Doc Upload
  const [newDocType, setNewDocType] = useState('Aadhaar');
  const [newDocNumber, setNewDocNumber] = useState('');
  const [newDocFile, setNewDocFile] = useState<File | null>(null);
  
  // Reservation state
  const [selectedReservation, setSelectedReservation] = useState<any | null>(null);
  const [newResGuestId, setNewResGuestId] = useState('');
  const [newResCheckin, setNewResCheckin] = useState('');
  const [newResCheckout, setNewResCheckout] = useState('');
  const [newResSource, setNewResSource] = useState('Walk-in');
  const [newResSourceDetails, setNewResSourceDetails] = useState('');
  const [newResNotes, setNewResNotes] = useState('');
  const [newResRooms, setNewResRooms] = useState<any[]>([]); // array of { room_id: number, has_extra_bed: boolean, guests: number[] }
  const [newResAdvance, setNewResAdvance] = useState('');
  const [newResPayMethod, setNewResPayMethod] = useState('Cash');
  const [newResTxRef, setNewResTxRef] = useState('');

  // Stay state
  const [selectedStay, setSelectedStay] = useState<any | null>(null);
  const [newStayExpectedCheckout, setNewStayExpectedCheckout] = useState('');
  const [newStayNotes, setNewStayNotes] = useState('');
  
  // Room Shift
  const [shiftRoomId, setShiftRoomId] = useState('');
  const [shiftReason, setShiftReason] = useState('');
  const [shiftOldRoomStatus, setShiftOldRoomStatus] = useState('');
  const [shiftPriceOverride, setShiftPriceOverride] = useState('');
  
  // Folio item
  const [newFolioType, setNewFolioType] = useState('room_charge');
  const [newFolioDesc, setNewFolioDesc] = useState('');
  const [newFolioAmount, setNewFolioAmount] = useState('');

  // Stay payment
  const [newStayPayMethod, setNewStayPayMethod] = useState('Cash');
  const [newStayPayAmount, setNewStayPayAmount] = useState('');
  const [newStayPayRef, setNewStayPayRef] = useState('');

  // Search/Filters
  const [guestSearch, setGuestSearch] = useState('');

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

      // Fetch guests list
      const gstRes = await fetch(`/api/hotels/${activeHotelId}/guests?search=${guestSearch}`, { headers: getHeaders() });
      const gstData = await gstRes.json();
      if (gstData.success) setGuests(gstData.data);

      // Fetch reservations list
      const rsvRes = await fetch(`/api/hotels/${activeHotelId}/reservations`, { headers: getHeaders() });
      const rsvData = await rsvRes.json();
      if (rsvData.success) setReservations(rsvData.data);

      // Fetch stays list
      const styRes = await fetch(`/api/hotels/${activeHotelId}/stays`, { headers: getHeaders() });
      const styData = await styRes.json();
      if (styData.success) setStays(styData.data);

    } catch (e) {
      showFeedback('danger', 'Failed to retrieve hotel-specific metadata.');
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
  }, [token, activeHotelId, activeTab, guestSearch]);

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

  // Create Guest
  const handleCreateGuest = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/guests`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          first_name: newGuestFirst,
          last_name: newGuestLast,
          email: newGuestEmail,
          phone: newGuestPhone
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', 'Guest profile registered successfully.');
        setNewGuestFirst('');
        setNewGuestLast('');
        setNewGuestEmail('');
        setNewGuestPhone('');
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to register guest.');
    }
  };

  // Upload Document
  const handleUploadDocument = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedGuest) return;
    try {
      const formData = new FormData();
      formData.append('document_type', newDocType);
      formData.append('document_number', newDocNumber);
      if (newDocFile) {
        formData.append('document_file', newDocFile);
      }

      const res = await fetch(`/api/hotels/${activeHotelId}/guests/${selectedGuest.id}/documents`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
        },
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', 'Identity document uploaded successfully.');
        setNewDocNumber('');
        setNewDocFile(null);
        // Refresh docs
        const docsRes = await fetch(`/api/hotels/${activeHotelId}/guests/${selectedGuest.id}/documents`, { headers: getHeaders() });
        const docsData = await docsRes.json();
        if (docsData.success) setGuestDocs(docsData.data);
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to upload document.');
    }
  };

  // Decrypt Document Number
  const handleDecryptDoc = async (docId: number) => {
    if (!selectedGuest) return;
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/guests/${selectedGuest.id}/documents/${docId}/decrypt`, {
        headers: getHeaders()
      });
      const data = await res.json();
      if (data.success) {
        setDecryptedDocNum(data.data.document_number);
        showFeedback('success', 'Document number decrypted successfully (audited).');
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to decrypt document.');
    }
  };

  // Create Reservation
  const handleCreateReservation = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const payload: any = {
        guest_id: parseInt(newResGuestId),
        checkin_date: newResCheckin,
        checkout_date: newResCheckout,
        booking_source: newResSource,
        booking_source_details: newResSourceDetails,
        notes: newResNotes,
        rooms: newResRooms
      };
      
      if (newResAdvance && parseFloat(newResAdvance) > 0) {
        payload['advance_payment'] = parseFloat(newResAdvance);
        payload['payment_method'] = newResPayMethod;
        payload['transaction_reference'] = newResTxRef;
      }

      const res = await fetch(`/api/hotels/${activeHotelId}/reservations`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', 'Reservation booked successfully.');
        setNewResGuestId('');
        setNewResCheckin('');
        setNewResCheckout('');
        setNewResNotes('');
        setNewResRooms([]);
        setNewResAdvance('');
        setNewResTxRef('');
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to create reservation.');
    }
  };

  // Cancel Reservation
  const handleCancelReservation = async (resId: number) => {
    if (!window.confirm('Are you sure you want to cancel this reservation?')) return;
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/reservations/${resId}/cancel`, {
        method: 'POST',
        headers: getHeaders()
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', 'Reservation cancelled successfully.');
        setSelectedReservation(null);
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to cancel reservation.');
    }
  };

  // Perform Check-in (Reservation-based or Walk-in)
  const handleCheckin = async (e: React.FormEvent, isWalkin: boolean) => {
    e.preventDefault();
    try {
      let payload: any = {};
      if (isWalkin) {
        payload = {
          expected_checkout_at: newStayExpectedCheckout,
          notes: newStayNotes,
          guest_id: parseInt(newResGuestId),
          rooms: newResRooms
        };
        if (newResAdvance && parseFloat(newResAdvance) > 0) {
          payload['advance_payment'] = parseFloat(newResAdvance);
          payload['payment_method'] = newResPayMethod;
          payload['transaction_reference'] = newResTxRef;
        }
      } else {
        if (!selectedReservation) return;
        payload = {
          reservation_id: selectedReservation.id,
          expected_checkout_at: selectedReservation.checkout_date + ' 12:00:00',
          notes: newStayNotes
        };
      }

      const res = await fetch(`/api/hotels/${activeHotelId}/stays/check-in`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', 'Check-in completed successfully. Room is now Occupied.');
        setSelectedReservation(null);
        setNewStayNotes('');
        setNewResRooms([]);
        setNewResAdvance('');
        setNewResTxRef('');
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Check-in operation failed.');
    }
  };

  // Perform Room Shift
  const handleRoomShift = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedStay) return;
    try {
      const payload: any = {
        new_room_id: parseInt(shiftRoomId),
        reason: shiftReason,
        old_room_status: shiftOldRoomStatus
      };
      if (shiftPriceOverride) {
        payload['price_per_night'] = parseFloat(shiftPriceOverride);
      }

      const res = await fetch(`/api/hotels/${activeHotelId}/stays/${selectedStay.id}/room-shift`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', 'Guest room shifted successfully.');
        setShiftRoomId('');
        setShiftReason('');
        setShiftOldRoomStatus('');
        setShiftPriceOverride('');
        // Refresh selected stay
        const stayRes = await fetch(`/api/hotels/${activeHotelId}/stays/${selectedStay.id}`, { headers: getHeaders() });
        const stayData = await stayRes.json();
        if (stayData.success) setSelectedStay(stayData.data);
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to perform room shift.');
    }
  };

  // Post Folio Item
  const handlePostFolioItem = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedStay) return;
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/stays/${selectedStay.id}/folio`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          item_type: newFolioType,
          description: newFolioDesc,
          amount: parseFloat(newFolioAmount),
          tax_amount: 0.00
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', 'Charge posted to stay folio.');
        setNewFolioDesc('');
        setNewFolioAmount('');
        // Refresh selected stay
        const stayRes = await fetch(`/api/hotels/${activeHotelId}/stays/${selectedStay.id}`, { headers: getHeaders() });
        const stayData = await stayRes.json();
        if (stayData.success) setSelectedStay(stayData.data);
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to post charge.');
    }
  };

  // Collect Folio Payment
  const handleCollectStayPayment = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedStay) return;
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/stays/${selectedStay.id}/payments`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          payment_method: newStayPayMethod,
          amount: parseFloat(newStayPayAmount),
          transaction_reference: newStayPayRef
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', 'Payment collected and applied to folio.');
        setNewStayPayAmount('');
        setNewStayPayRef('');
        // Refresh selected stay
        const stayRes = await fetch(`/api/hotels/${activeHotelId}/stays/${selectedStay.id}`, { headers: getHeaders() });
        const stayData = await stayRes.json();
        if (stayData.success) setSelectedStay(stayData.data);
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to record payment.');
    }
  };

  // Complete Checkout
  const handleCheckout = async (overrideUnsettled: boolean) => {
    if (!selectedStay) return;
    if (!window.confirm('Are you sure you want to perform checkout? This will release the room to cleaning.')) return;
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/stays/${selectedStay.id}/check-out`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          override_unsettled: overrideUnsettled
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', 'Checkout completed successfully. Room released for cleaning.');
        setSelectedStay(null);
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to complete checkout.');
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
            <img src="/icon.png" alt="HMS Logo" className="login-logo-img" style={{ width: '64px', height: '64px', marginBottom: '16px' }} />
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
        <div className="brand">
          <img src="/icon.png" alt="HMS Logo" style={{ width: '32px', height: '32px' }} />
          <span>HMS <span className="brand-accent">Core</span></span>
        </div>
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
          <li className="nav-item">
            <button
              onClick={() => {
                setActiveTab('guests');
                setSelectedGuest(null);
                setDecryptedDocNum(null);
              }}
              className={`nav-button ${activeTab === 'guests' ? 'active' : ''}`}
            >
              Guests Directory
            </button>
          </li>
          <li className="nav-item">
            <button
              onClick={() => {
                setActiveTab('reservations');
                setSelectedReservation(null);
              }}
              className={`nav-button ${activeTab === 'reservations' ? 'active' : ''}`}
            >
              Bookings & Calendar
            </button>
          </li>
          <li className="nav-item">
            <button
              onClick={() => {
                setActiveTab('stays');
                setSelectedStay(null);
              }}
              className={`nav-button ${activeTab === 'stays' ? 'active' : ''}`}
            >
              Checked In Stays
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

          {/* 6. GUESTS DIRECTORY */}
          {activeTab === 'guests' && (
            <div className="grid-2col" style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
              <div className="glass-panel">
                <h2 className="page-title">Register New Guest</h2>
                <form onSubmit={handleCreateGuest}>
                  <div className="form-group" style={{ marginBottom: '12px' }}>
                    <label className="form-label">First Name</label>
                    <input
                      type="text"
                      className="form-input"
                      value={newGuestFirst}
                      onChange={(e) => setNewGuestFirst(e.target.value)}
                      required
                    />
                  </div>
                  <div className="form-group" style={{ marginBottom: '12px' }}>
                    <label className="form-label">Last Name</label>
                    <input
                      type="text"
                      className="form-input"
                      value={newGuestLast}
                      onChange={(e) => setNewGuestLast(e.target.value)}
                      required
                    />
                  </div>
                  <div className="form-group" style={{ marginBottom: '12px' }}>
                    <label className="form-label">Email Address</label>
                    <input
                      type="email"
                      className="form-input"
                      value={newGuestEmail}
                      onChange={(e) => setNewGuestEmail(e.target.value)}
                    />
                  </div>
                  <div className="form-group" style={{ marginBottom: '16px' }}>
                    <label className="form-label">Phone Number</label>
                    <input
                      type="text"
                      className="form-input"
                      value={newGuestPhone}
                      onChange={(e) => setNewGuestPhone(e.target.value)}
                    />
                  </div>
                  <button type="submit" className="btn">Register Profile</button>
                </form>

                <div style={{ marginTop: '30px' }}>
                  <h3 className="page-title" style={{ fontSize: '18px' }}>Search Directory</h3>
                  <input
                    type="text"
                    className="form-input"
                    placeholder="Search by name, email, or phone..."
                    value={guestSearch}
                    onChange={(e) => setGuestSearch(e.target.value)}
                  />
                  <div className="table-wrapper" style={{ marginTop: '15px', maxHeight: '300px', overflowY: 'auto' }}>
                    <table className="table">
                      <thead>
                        <tr>
                          <th>Name</th>
                          <th>Phone</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        {guests.map((g) => (
                          <tr key={g.id} className={selectedGuest?.id === g.id ? 'active-row' : ''} style={{ cursor: 'pointer' }} onClick={async () => {
                            setSelectedGuest(g);
                            setDecryptedDocNum(null);
                            const docsRes = await fetch(`/api/hotels/${activeHotelId}/guests/${g.id}/documents`, { headers: getHeaders() });
                            const docsData = await docsRes.json();
                            if (docsData.success) setGuestDocs(docsData.data);
                          }}>
                            <td><strong>{g.first_name} {g.last_name}</strong></td>
                            <td>{g.phone || 'N/A'}</td>
                            <td>
                              <button className="btn btn-secondary btn-sm" style={{ padding: '4px 8px', fontSize: '12px' }}>View Docs</button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

              {selectedGuest && (
                <div className="glass-panel">
                  <h2 className="page-title">Guest Profile Details</h2>
                  <div style={{ background: 'rgba(0,0,0,0.05)', padding: '15px', borderRadius: '8px', marginBottom: '20px' }}>
                    <p style={{ margin: '0 0 8px 0' }}><strong>Full Name:</strong> {selectedGuest.first_name} {selectedGuest.last_name}</p>
                    <p style={{ margin: '0 0 8px 0' }}><strong>Email:</strong> {selectedGuest.email || 'N/A'}</p>
                    <p style={{ margin: '0 0 8px 0' }}><strong>Phone:</strong> {selectedGuest.phone || 'N/A'}</p>
                  </div>

                  <h3 className="page-title" style={{ fontSize: '18px' }}>Uploaded Identity Documents</h3>
                  {guestDocs.length === 0 ? (
                    <p style={{ color: '#9ca3af', fontStyle: 'italic' }}>No documents uploaded for this guest yet.</p>
                  ) : (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '10px', marginBottom: '20px' }}>
                      {guestDocs.map((doc) => (
                        <div key={doc.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', background: 'rgba(255,255,255,0.4)', padding: '10px 15px', borderRadius: '6px', border: '1px solid rgba(0,0,0,0.05)' }}>
                          <div>
                            <span style={{ fontWeight: '600' }}>{doc.document_type}</span>: <code style={{ background: 'rgba(0,0,0,0.05)', padding: '2px 4px', borderRadius: '4px' }}>{doc.document_number_masked}</code>
                            {doc.file_path && (
                              <div style={{ marginTop: '4px', fontSize: '12px' }}>
                                <a href={doc.file_path} target="_blank" rel="noopener noreferrer" style={{ color: 'var(--primary)', textDecoration: 'underline' }}>View Uploaded File</a>
                              </div>
                            )}
                          </div>
                          <div style={{ display: 'flex', gap: '8px' }}>
                            <button onClick={() => handleDecryptDoc(doc.id)} className="btn btn-secondary btn-sm" style={{ padding: '6px 10px', fontSize: '12px' }}>Decrypt</button>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}

                  {decryptedDocNum && (
                    <div className="alert alert-success" style={{ marginBottom: '20px' }}>
                      <strong>Decrypted Full Number:</strong> <code style={{ fontSize: '16px', fontWeight: 'bold' }}>{decryptedDocNum}</code>
                    </div>
                  )}

                  <h3 className="page-title" style={{ fontSize: '18px' }}>Upload Identity Document</h3>
                  <form onSubmit={handleUploadDocument}>
                    <div className="form-group" style={{ marginBottom: '12px' }}>
                      <label className="form-label">Document Type</label>
                      <select className="select-dropdown" value={newDocType} onChange={(e) => setNewDocType(e.target.value)}>
                        <option value="Aadhaar">Aadhaar (12 Digits)</option>
                        <option value="Passport">Passport</option>
                        <option value="Driving License">Driving License</option>
                        <option value="Voter ID">Voter ID</option>
                      </select>
                    </div>
                    <div className="form-group" style={{ marginBottom: '12px' }}>
                      <label className="form-label">Document Number</label>
                      <input
                        type="text"
                        className="form-input"
                        placeholder="Enter full number for encryption..."
                        value={newDocNumber}
                        onChange={(e) => setNewDocNumber(e.target.value)}
                        required
                      />
                    </div>
                    <div className="form-group" style={{ marginBottom: '16px' }}>
                      <label className="form-label">Document Scan File (Optional: JPG, PNG, PDF)</label>
                      <input
                        type="file"
                        className="form-input"
                        style={{ height: 'auto', padding: '6px' }}
                        onChange={(e) => {
                          if (e.target.files && e.target.files.length > 0) {
                            setNewDocFile(e.target.files[0]);
                          }
                        }}
                      />
                    </div>
                    <button type="submit" className="btn">Secure Upload & Encrypt</button>
                  </form>
                </div>
              )}
            </div>
          )}

          {/* 7. BOOKINGS & CALENDAR */}
          {activeTab === 'reservations' && (
            <div className="grid-2col" style={{ display: 'grid', gridTemplateColumns: '1fr 1.2fr', gap: '20px' }}>
              <div className="glass-panel">
                <h2 className="page-title">Create Reservation</h2>
                <form onSubmit={handleCreateReservation}>
                  <div className="form-group" style={{ marginBottom: '12px' }}>
                    <label className="form-label">Primary Guest</label>
                    <select className="select-dropdown" value={newResGuestId} onChange={(e) => setNewResGuestId(e.target.value)} required>
                      <option value="">-- Select Guest --</option>
                      {guests.map((g) => (
                        <option key={g.id} value={g.id}>{g.first_name} {g.last_name} ({g.phone || 'No phone'})</option>
                      ))}
                    </select>
                  </div>
                  <div className="form-grid" style={{ gap: '10px', marginBottom: '12px' }}>
                    <div className="form-group">
                      <label className="form-label">Check-in Date</label>
                      <input type="date" className="form-input" value={newResCheckin} onChange={(e) => setNewResCheckin(e.target.value)} required />
                    </div>
                    <div className="form-group">
                      <label className="form-label">Check-out Date</label>
                      <input type="date" className="form-input" value={newResCheckout} onChange={(e) => setNewResCheckout(e.target.value)} required />
                    </div>
                  </div>
                  <div className="form-grid" style={{ gap: '10px', marginBottom: '12px' }}>
                    <div className="form-group">
                      <label className="form-label">Booking Source</label>
                      <select className="select-dropdown" value={newResSource} onChange={(e) => setNewResSource(e.target.value)}>
                        <option value="Walk-in">Walk-in</option>
                        <option value="Phone">Phone</option>
                        <option value="Other">Other</option>
                      </select>
                    </div>
                    <div className="form-group">
                      <label className="form-label">Source Details (Optional)</label>
                      <input type="text" className="form-input" placeholder="e.g. Booking.com, agent name" value={newResSourceDetails} onChange={(e) => setNewResSourceDetails(e.target.value)} />
                    </div>
                  </div>

                  <div className="form-group" style={{ marginBottom: '12px' }}>
                    <label className="form-label">Select Room (Must be available)</label>
                    <div style={{ maxHeight: '180px', overflowY: 'auto', background: 'rgba(0,0,0,0.05)', padding: '10px', borderRadius: '6px', display: 'flex', flexDirection: 'column', gap: '8px' }}>
                      {rooms.filter(r => r.status === 'Available').map((r) => {
                        const isChecked = newResRooms.some(nr => nr.room_id === r.id);
                        return (
                          <div key={r.id} style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', background: 'rgba(255,255,255,0.4)', padding: '6px 10px', borderRadius: '4px' }}>
                            <label style={{ display: 'flex', alignItems: 'center', gap: '8px', cursor: 'pointer' }}>
                              <input
                                type="checkbox"
                                checked={isChecked}
                                onChange={(e) => {
                                  if (e.target.checked) {
                                    setNewResRooms([...newResRooms, { room_id: r.id, has_extra_bed: false, guests: [] }]);
                                  } else {
                                    setNewResRooms(newResRooms.filter(nr => nr.room_id !== r.id));
                                  }
                                }}
                              />
                              Room {r.room_number} ({r.room_type_name})
                            </label>
                            {isChecked && (
                              <label style={{ display: 'flex', alignItems: 'center', gap: '4px', fontSize: '12px', cursor: 'pointer' }}>
                                <input
                                  type="checkbox"
                                  checked={newResRooms.find(nr => nr.room_id === r.id)?.has_extra_bed || false}
                                  onChange={(e) => {
                                    setNewResRooms(newResRooms.map(nr => nr.room_id === r.id ? { ...nr, has_extra_bed: e.target.checked } : nr));
                                  }}
                                />
                                Extra Bed
                              </label>
                            )}
                          </div>
                        );
                      })}
                    </div>
                  </div>

                  <div style={{ background: 'rgba(0,0,0,0.03)', padding: '10px', borderRadius: '6px', marginBottom: '16px' }}>
                    <h4 style={{ margin: '0 0 8px 0', fontSize: '14px' }}>Advance Payment (Optional)</h4>
                    <div className="form-grid" style={{ gap: '10px' }}>
                      <div className="form-group">
                        <label className="form-label" style={{ fontSize: '11px' }}>Amount (INR)</label>
                        <input type="number" className="form-input" placeholder="0.00" value={newResAdvance} onChange={(e) => setNewResAdvance(e.target.value)} />
                      </div>
                      <div className="form-group">
                        <label className="form-label" style={{ fontSize: '11px' }}>Payment Method</label>
                        <select className="select-dropdown" value={newResPayMethod} onChange={(e) => setNewResPayMethod(e.target.value)}>
                          <option value="Cash">Cash</option>
                          <option value="Card">Card</option>
                          <option value="UPI">UPI</option>
                          <option value="Bank">Bank Transfer</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <div className="form-group" style={{ marginBottom: '16px' }}>
                    <label className="form-label">Booking Notes</label>
                    <textarea className="form-input" style={{ height: '60px' }} value={newResNotes} onChange={(e) => setNewResNotes(e.target.value)} />
                  </div>

                  <button type="submit" className="btn">Book Reservation</button>
                </form>
              </div>

              <div className="glass-panel">
                <h2 className="page-title">Reservations List</h2>
                <div className="table-wrapper">
                  <table className="table">
                    <thead>
                      <tr>
                        <th>Check-in</th>
                        <th>Booker</th>
                        <th>Rooms</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      {reservations.map((r) => (
                        <tr key={r.id} className={selectedReservation?.id === r.id ? 'active-row' : ''} style={{ cursor: 'pointer' }} onClick={() => {
                          setSelectedReservation(r);
                          setNewStayNotes(r.notes || '');
                        }}>
                          <td><strong>{r.checkin_date}</strong></td>
                          <td>{r.first_name} {r.last_name}</td>
                          <td>
                            {r.rooms.map((rm: any) => rm.room_number).join(', ')}
                          </td>
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

                {selectedReservation && (
                  <div style={{ marginTop: '25px', padding: '15px', border: '1px solid rgba(0,0,0,0.08)', borderRadius: '8px', background: 'rgba(255,255,255,0.4)' }}>
                    <h3 className="page-title" style={{ fontSize: '18px', margin: '0 0 12px 0' }}>Reservation Detail - #{selectedReservation.id}</h3>
                    <p style={{ margin: '0 0 8px 0' }}><strong>Guest Booker:</strong> {selectedReservation.first_name} {selectedReservation.last_name} ({selectedReservation.phone})</p>
                    <p style={{ margin: '0 0 8px 0' }}><strong>Dates:</strong> {selectedReservation.checkin_date} to {selectedReservation.checkout_date}</p>
                    <p style={{ margin: '0 0 8px 0' }}><strong>Source:</strong> {selectedReservation.booking_source} ({selectedReservation.booking_source_details || 'No details'})</p>
                    <p style={{ margin: '0 0 12px 0' }}><strong>Status:</strong> <span className={`room-status-badge status-${selectedReservation.status}`}>{selectedReservation.status}</span></p>

                    {selectedReservation.status === 'Confirmed' && (
                      <div style={{ display: 'flex', gap: '10px', marginTop: '15px' }}>
                        <form onSubmit={(e) => handleCheckin(e, false)} style={{ width: '100%' }}>
                          <div className="form-group" style={{ marginBottom: '10px' }}>
                            <label className="form-label" style={{ fontSize: '12px' }}>Check-in Arrival Notes</label>
                            <input type="text" className="form-input" placeholder="Any arrival requests..." value={newStayNotes} onChange={(e) => setNewStayNotes(e.target.value)} />
                          </div>
                          <div style={{ display: 'flex', gap: '10px' }}>
                            <button type="submit" className="btn" style={{ background: 'var(--status-available)', color: 'white' }}>Complete Check-in</button>
                            <button type="button" onClick={() => handleCancelReservation(selectedReservation.id)} className="btn btn-secondary btn-danger" style={{ background: 'var(--status-occupied)', color: 'white' }}>Cancel Booking</button>
                          </div>
                        </form>
                      </div>
                    )}
                  </div>
                )}
              </div>
            </div>
          )}

          {/* 8. ACTIVE STAYS DASHBOARD */}
          {activeTab === 'stays' && (
            <div className="grid-2col" style={{ display: 'grid', gridTemplateColumns: '1fr 1.5fr', gap: '20px' }}>
              <div className="glass-panel">
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' }}>
                  <h2 className="page-title" style={{ margin: 0 }}>Active Hotel Stays</h2>
                </div>
                
                <div className="table-wrapper" style={{ maxHeight: '600px', overflowY: 'auto' }}>
                  <table className="table">
                    <thead>
                      <tr>
                        <th>Stay ID</th>
                        <th>Rooms</th>
                        <th>Check-in At</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      {stays.map((s) => (
                        <tr key={s.id} className={selectedStay?.id === s.id ? 'active-row' : ''} style={{ cursor: 'pointer' }} onClick={async () => {
                          const stayRes = await fetch(`/api/hotels/${activeHotelId}/stays/${s.id}`, { headers: getHeaders() });
                          const stayData = await stayRes.json();
                          if (stayData.success) {
                            setSelectedStay(stayData.data);
                            setNewStayExpectedCheckout(stayData.data.expected_checkout_at);
                          }
                        }}>
                          <td><strong>Stay #{s.id}</strong></td>
                          <td>
                            {s.rooms.map((rm: any) => rm.room_number).join(', ')}
                          </td>
                          <td>{new Date(s.checkin_at).toLocaleDateString()}</td>
                          <td>
                            <span className={`room-status-badge status-${s.status}`} style={{ margin: 0 }}>
                              {s.status}
                            </span>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>

                <div style={{ marginTop: '25px', borderTop: '1px solid rgba(0,0,0,0.08)', paddingTop: '20px' }}>
                  <h3 className="page-title" style={{ fontSize: '18px' }}>Walk-in Guest Check-in</h3>
                  <form onSubmit={(e) => handleCheckin(e, true)}>
                    <div className="form-group" style={{ marginBottom: '10px' }}>
                      <label className="form-label">Primary Guest</label>
                      <select className="select-dropdown" value={newResGuestId} onChange={(e) => setNewResGuestId(e.target.value)} required>
                        <option value="">-- Select Guest --</option>
                        {guests.map((g) => (
                          <option key={g.id} value={g.id}>{g.first_name} {g.last_name}</option>
                        ))}
                      </select>
                    </div>
                    <div className="form-group" style={{ marginBottom: '10px' }}>
                      <label className="form-label">Expected Checkout Time</label>
                      <input type="datetime-local" className="form-input" value={newStayExpectedCheckout} onChange={(e) => setNewStayExpectedCheckout(e.target.value)} required />
                    </div>
                    <div className="form-group" style={{ marginBottom: '10px' }}>
                      <label className="form-label">Select Room(s)</label>
                      <div style={{ maxHeight: '120px', overflowY: 'auto', background: 'rgba(0,0,0,0.05)', padding: '8px', borderRadius: '6px', display: 'flex', flexDirection: 'column', gap: '6px' }}>
                        {rooms.filter(r => r.status === 'Available').map((r) => {
                          const isChecked = newResRooms.some(nr => nr.room_id === r.id);
                          return (
                            <label key={r.id} style={{ display: 'flex', alignItems: 'center', gap: '8px', cursor: 'pointer' }}>
                              <input
                                type="checkbox"
                                checked={isChecked}
                                onChange={(e) => {
                                  if (e.target.checked) {
                                    setNewResRooms([...newResRooms, { room_id: r.id, has_extra_bed: false }]);
                                  } else {
                                    setNewResRooms(newResRooms.filter(nr => nr.room_id !== r.id));
                                  }
                                }}
                              />
                              Room {r.room_number} ({r.room_type_name})
                            </label>
                          );
                        })}
                      </div>
                    </div>
                    <div style={{ background: 'rgba(0,0,0,0.03)', padding: '10px', borderRadius: '6px', marginBottom: '12px' }}>
                      <h4 style={{ margin: '0 0 6px 0', fontSize: '12px' }}>Collect Advance (Optional)</h4>
                      <div className="form-grid" style={{ gap: '8px' }}>
                        <input type="number" className="form-input" placeholder="Amt" value={newResAdvance} onChange={(e) => setNewResAdvance(e.target.value)} />
                        <select className="select-dropdown" value={newResPayMethod} onChange={(e) => setNewResPayMethod(e.target.value)}>
                          <option value="Cash">Cash</option>
                          <option value="Card">Card</option>
                          <option value="UPI">UPI</option>
                        </select>
                      </div>
                    </div>
                    <button type="submit" className="btn" style={{ background: 'var(--status-available)', color: 'white' }}>Perform Walk-in Check-in</button>
                  </form>
                </div>
              </div>

              {selectedStay && (
                <div className="glass-panel">
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', borderBottom: '1px solid rgba(0,0,0,0.08)', paddingBottom: '12px', marginBottom: '15px' }}>
                    <h2 className="page-title" style={{ margin: 0 }}>Stay Folio & Details</h2>
                    <span className={`room-status-badge status-${selectedStay.status}`}>{selectedStay.status}</span>
                  </div>

                  <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px', marginBottom: '20px', background: 'rgba(0,0,0,0.03)', padding: '15px', borderRadius: '8px' }}>
                    <div>
                      <p style={{ margin: '0 0 6px 0' }}><strong>Booker:</strong> {selectedStay.booker_first_name} {selectedStay.booker_last_name} ({selectedStay.booker_phone})</p>
                      <p style={{ margin: '0 0 6px 0' }}><strong>Checked In:</strong> {new Date(selectedStay.checkin_at).toLocaleString()}</p>
                      <p style={{ margin: '0' }}><strong>Expected Out:</strong> {new Date(selectedStay.expected_checkout_at).toLocaleString()}</p>
                    </div>
                    <div>
                      <p style={{ margin: '0 0 6px 0' }}><strong>Rooms:</strong> {selectedStay.rooms.map((r: any) => `Room ${r.room_number} (${r.checked_out_at ? 'Shifted Out' : 'Active'})`).join(', ')}</p>
                      {selectedStay.status === 'Active' && (
                        <div style={{ marginTop: '8px' }}>
                          <button onClick={() => {
                            setShiftRoomId('');
                            setShiftOldRoomStatus('Cleaning');
                          }} className="btn btn-secondary btn-sm">Perform Room Shift</button>
                        </div>
                      )}
                    </div>
                  </div>

                  {/* ROOM SHIFT FORM IF TRIGGERED */}
                  {shiftOldRoomStatus && selectedStay.status === 'Active' && (
                    <div style={{ border: '1px dashed var(--primary)', borderRadius: '8px', padding: '15px', marginBottom: '20px', background: 'rgba(157, 59, 248, 0.05)' }}>
                      <h3 className="page-title" style={{ fontSize: '16px', margin: '0 0 10px 0' }}>Perform Room Shift</h3>
                      <form onSubmit={handleRoomShift}>
                        <div className="form-grid" style={{ gap: '10px', marginBottom: '10px' }}>
                          <div className="form-group">
                            <label className="form-label" style={{ fontSize: '11px' }}>Shift to Room</label>
                            <select className="select-dropdown" value={shiftRoomId} onChange={(e) => setShiftRoomId(e.target.value)} required>
                              <option value="">-- Target Room --</option>
                              {rooms.filter(r => r.status === 'Available').map(r => (
                                <option key={r.id} value={r.id}>Room {r.room_number} ({r.room_type_name})</option>
                              ))}
                            </select>
                          </div>
                          <div className="form-group">
                            <label className="form-label" style={{ fontSize: '11px' }}>Old Room Status</label>
                            <select className="select-dropdown" value={shiftOldRoomStatus} onChange={(e) => setShiftOldRoomStatus(e.target.value)}>
                              <option value="Cleaning">Cleaning</option>
                              <option value="Maintenance">Maintenance</option>
                              <option value="Blocked">Blocked</option>
                            </select>
                          </div>
                        </div>
                        <div className="form-grid" style={{ gap: '10px', marginBottom: '12px' }}>
                          <div className="form-group">
                            <label className="form-label" style={{ fontSize: '11px' }}>Shift Reason</label>
                            <input type="text" className="form-input" placeholder="e.g. Guest requested high floor" value={shiftReason} onChange={(e) => setShiftReason(e.target.value)} required />
                          </div>
                          <div className="form-group">
                            <label className="form-label" style={{ fontSize: '11px' }}>Override Price per Night (Optional)</label>
                            <input type="number" className="form-input" placeholder="Keep current rate" value={shiftPriceOverride} onChange={(e) => setShiftPriceOverride(e.target.value)} />
                          </div>
                        </div>
                        <button type="submit" className="btn btn-sm">Confirm Room Shift</button>
                      </form>
                    </div>
                  )}

                  {/* FOLIO ITEM LEDGER */}
                  <h3 className="page-title" style={{ fontSize: '18px' }}>Stay Folio Ledger</h3>
                  <div className="table-wrapper" style={{ maxHeight: '200px', overflowY: 'auto', marginBottom: '15px' }}>
                    <table className="table" style={{ fontSize: '13px' }}>
                      <thead>
                        <tr>
                          <th>Description</th>
                          <th>Type</th>
                          <th style={{ textAlign: 'right' }}>Amount</th>
                        </tr>
                      </thead>
                      <tbody>
                        {selectedStay.folio.map((item: any) => (
                          <tr key={item.id}>
                            <td>{item.description}</td>
                            <td><span style={{ fontSize: '11px', textTransform: 'uppercase', color: '#6b7280' }}>{item.item_type}</span></td>
                            <td style={{ textAlign: 'right', fontWeight: '600', color: parseFloat(item.amount) < 0 ? 'var(--status-available)' : 'inherit' }}>
                              {parseFloat(item.amount).toFixed(2)} INR
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>

                  {/* SUMMARY CHARGES SHEET */}
                  {(() => {
                    let totalCharges = 0.00;
                    let totalPayments = 0.00;
                    selectedStay.folio.forEach((item: any) => {
                      const amt = parseFloat(item.amount);
                      if (amt > 0) totalCharges += amt;
                      else totalPayments += Math.abs(amt);
                    });
                    const balance = totalCharges - totalPayments;
                    return (
                      <div style={{ display: 'flex', justifyContent: 'space-between', background: 'rgba(0,0,0,0.05)', padding: '12px 15px', borderRadius: '6px', marginBottom: '20px', fontWeight: 'bold' }}>
                        <div>Charges: {totalCharges.toFixed(2)}</div>
                        <div style={{ color: 'var(--status-available)' }}>Payments: {totalPayments.toFixed(2)}</div>
                        <div style={{ color: Math.abs(balance) > 0.01 ? 'var(--status-occupied)' : 'inherit' }}>
                          Outstanding: {balance.toFixed(2)} INR
                        </div>
                      </div>
                    );
                  })()}

                  {/* ACTION CHARGES & PAYMENTS */}
                  {selectedStay.status === 'Active' && (
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px', marginBottom: '20px' }}>
                      <div style={{ border: '1px solid rgba(0,0,0,0.08)', padding: '12px', borderRadius: '6px' }}>
                        <h4 style={{ margin: '0 0 8px 0', fontSize: '14px' }}>Post Custom Charge</h4>
                        <form onSubmit={handlePostFolioItem}>
                          <select className="select-dropdown select-dropdown-sm" style={{ marginBottom: '6px', height: '32px', fontSize: '12px' }} value={newFolioType} onChange={(e) => setNewFolioType(e.target.value)}>
                            <option value="kitchen_order">Kitchen Order</option>
                            <option value="extra_bed">Extra Bed</option>
                            <option value="late_checkout">Late Checkout</option>
                            <option value="adjustment">Adjustment Override</option>
                          </select>
                          <input type="text" className="form-input form-input-sm" style={{ marginBottom: '6px', height: '32px', fontSize: '12px' }} placeholder="Charge description..." value={newFolioDesc} onChange={(e) => setNewFolioDesc(e.target.value)} required />
                          <input type="number" className="form-input form-input-sm" style={{ marginBottom: '8px', height: '32px', fontSize: '12px' }} placeholder="Amount INR" value={newFolioAmount} onChange={(e) => setNewFolioAmount(e.target.value)} required />
                          <button type="submit" className="btn btn-sm btn-secondary" style={{ width: '100%' }}>Post Charge</button>
                        </form>
                      </div>

                      <div style={{ border: '1px solid rgba(0,0,0,0.08)', padding: '12px', borderRadius: '6px' }}>
                        <h4 style={{ margin: '0 0 8px 0', fontSize: '14px' }}>Collect Stay Payment</h4>
                        <form onSubmit={handleCollectStayPayment}>
                          <select className="select-dropdown select-dropdown-sm" style={{ marginBottom: '6px', height: '32px', fontSize: '12px' }} value={newStayPayMethod} onChange={(e) => setNewStayPayMethod(e.target.value)}>
                            <option value="Cash">Cash</option>
                            <option value="Card">Card</option>
                            <option value="UPI">UPI</option>
                            <option value="Bank">Bank Transfer</option>
                          </select>
                          <input type="number" className="form-input form-input-sm" style={{ marginBottom: '6px', height: '32px', fontSize: '12px' }} placeholder="Amount INR" value={newStayPayAmount} onChange={(e) => setNewStayPayAmount(e.target.value)} required />
                          <input type="text" className="form-input form-input-sm" style={{ marginBottom: '8px', height: '32px', fontSize: '12px' }} placeholder="Tx Ref/UTR (Optional)" value={newStayPayRef} onChange={(e) => setNewStayPayRef(e.target.value)} />
                          <button type="submit" className="btn btn-sm btn-secondary" style={{ width: '100%', background: 'var(--status-available)', color: 'white' }}>Apply Payment</button>
                        </form>
                      </div>
                    </div>
                  )}

                  {/* CHECKOUT WORKFLOW ACTION PANEL */}
                  {selectedStay.status === 'Active' && (
                    <div style={{ background: 'rgba(239, 68, 68, 0.05)', border: '1px solid var(--status-occupied)', padding: '15px', borderRadius: '8px', display: 'flex', flexDirection: 'column', gap: '10px' }}>
                      <h4 style={{ margin: 0, color: 'var(--status-occupied)' }}>Checkout Settlement Panel</h4>
                      <p style={{ margin: 0, fontSize: '12px', color: '#64748b' }}>Completing checkout will post any remaining night charges to this stay, close the folio, and move room to Cleaning.</p>
                      
                      <div style={{ display: 'flex', gap: '10px', marginTop: '5px' }}>
                        <button onClick={() => handleCheckout(false)} className="btn" style={{ background: 'var(--status-occupied)', color: 'white', flex: 1 }}>
                          Standard Checkout (Zero Bal)
                        </button>
                        <button onClick={() => handleCheckout(true)} className="btn btn-secondary" style={{ flex: 1, borderColor: 'var(--status-occupied)', color: 'var(--status-occupied)' }}>
                          Override & Checkout (Unsettled)
                        </button>
                      </div>
                    </div>
                  )}
                </div>
              )}
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
            <div style={{ marginBottom: '20px', borderBottom: '1px solid rgba(0,0,0,0.08)', paddingBottom: '12px' }}>
              <h4 style={{ margin: '0 0 10px 0', color: '#0f172a' }}>Weekend Price Overrides</h4>
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
            <div style={{ marginBottom: '20px', borderBottom: '1px solid rgba(0,0,0,0.08)', paddingBottom: '12px' }}>
              <h4 style={{ margin: '0 0 10px 0', color: '#0f172a' }}>Seasonal Rate Rule</h4>
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
              <h4 style={{ margin: '0 0 10px 0', color: '#0f172a' }}>Holiday Rate Override</h4>
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
