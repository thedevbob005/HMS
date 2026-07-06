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
  const [activeTab, setActiveTab] = useState<'dashboard' | 'hotels' | 'staff' | 'room-config' | 'rooms' | 'guests' | 'reservations' | 'stays' | 'invoices' | 'reports' | 'notifications' | 'housekeeping' | 'inventory' | 'kitchen' | 'employees'>('dashboard');

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

  // Search/Filters
  const [guestSearch, setGuestSearch] = useState('');

  // Phase 4 State
  const [invoices, setInvoices] = useState<any[]>([]);
  const [selectedInvoice, setSelectedInvoice] = useState<any | null>(null);
  const [messages, setMessages] = useState<any[]>([]);
  const [collections, setCollections] = useState<any[]>([]);
  const [colStartDate, setColStartDate] = useState(new Date(Date.now() - 7 * 86400000).toISOString().split('T')[0]);
  const [colEndDate, setColEndDate] = useState(new Date().toISOString().split('T')[0]);
  const [splitPayments, setSplitPayments] = useState<{ method: string, amount: string, ref: string }[]>([{ method: 'Cash', amount: '', ref: '' }]);

  // Phase 5 State
  const [verifyingDoc, setVerifyingDoc] = useState<any | null>(null);
  const [otpCode, setOtpCode] = useState('');
  const [otpClientId, setOtpClientId] = useState('');
  const [fallbackReason, setFallbackReason] = useState('');
  const [verifLogs, setVerifLogs] = useState<any[]>([]);
  const [showOtpModal, setShowOtpModal] = useState(false);
  const [showFallbackModal, setShowFallbackModal] = useState(false);
  const [showLogsModal, setShowLogsModal] = useState(false);

  // Phase 6 State
  const [housekeepingTasks, setHousekeepingTasks] = useState<any[]>([]);
  const [hkStatusFilter, setHkStatusFilter] = useState('');
  const [hkTypeFilter, setHkTypeFilter] = useState('');
  const [newHkRoomId, setNewHkRoomId] = useState('');
  const [newHkType, setNewHkType] = useState('cleaning');
  const [newHkPriority, setNewHkPriority] = useState('medium');
  const [newHkNotes, setNewHkNotes] = useState('');
  const [newHkAssignee, setNewHkAssignee] = useState('');

  // Phase 7 State
  const [inventoryItems, setInventoryItems] = useState<any[]>([]);
  const [vendors, setVendors] = useState<any[]>([]);
  const [purchaseOrders, setPurchaseOrders] = useState<any[]>([]);
  const [goodsReceipts, setGoodsReceipts] = useState<any[]>([]);
  const [selectedHkItem, setSelectedHkItem] = useState<any | null>(null);
  const [itemLedger, setItemLedger] = useState<any[]>([]);
  const [showLedgerModal, setShowLedgerModal] = useState(false);
  const [showAdjustModal, setShowAdjustModal] = useState(false);
  const [adjustQty, setAdjustQty] = useState('');
  const [adjustCost, setAdjustCost] = useState('');
  const [adjustReason, setAdjustReason] = useState('');
  const [showCreateItemModal, setShowCreateItemModal] = useState(false);
  const [showCreateVendorModal, setShowCreateVendorModal] = useState(false);
  const [showCreatePoModal, setShowCreatePoModal] = useState(false);
  const [showGrnModal, setShowGrnModal] = useState(false);
  const [selectedPo, setSelectedPo] = useState<any | null>(null);
  const [grnItems, setGrnItems] = useState<any[]>([]);
  const [grnNotes, setGrnNotes] = useState('');
  const [receivedDate, setReceivedDate] = useState(new Date().toISOString().split('T')[0]);
  const [newItemSku, setNewItemSku] = useState('');
  const [newItemName, setNewItemName] = useState('');
  const [newItemCategory, setNewItemCategory] = useState('');
  const [newItemUom, setNewItemUom] = useState('Pcs');
  const [newItemMinStock, setNewItemMinStock] = useState('0');
  const [newVendorName, setNewVendorName] = useState('');
  const [newVendorContact, setNewVendorContact] = useState('');
  const [newVendorPhone, setNewVendorPhone] = useState('');
  const [newVendorEmail, setNewVendorEmail] = useState('');
  const [newVendorAddress, setNewVendorAddress] = useState('');
  const [newVendorGst, setNewVendorGst] = useState('');
  const [newPoVendorId, setNewPoVendorId] = useState('');
  const [newPoNotes, setNewPoNotes] = useState('');
  const [newPoItems, setNewPoItems] = useState<{ inventory_item_id: string, quantity: string, unit_price: string }[]>([{ inventory_item_id: '', quantity: '', unit_price: '' }]);
  const [invSubTab, setInvSubTab] = useState<'stock' | 'vendors' | 'po' | 'grn'>('stock');

  // Phase 8 State
  const [kitchenItems, setKitchenItems] = useState<any[]>([]);
  const [kitchenOrders, setKitchenOrders] = useState<any[]>([]);
  const [kitchenCostingSheet, setKitchenCostingSheet] = useState<any[]>([]);
  const [selectedKItem, setSelectedKItem] = useState<any | null>(null);
  const [showRecipeModal, setShowRecipeModal] = useState(false);
  const [recipeIngredients, setRecipeIngredients] = useState<{ inventory_item_id: string, quantity: string }[]>([{ inventory_item_id: '', quantity: '' }]);
  const [recipeInstructions, setRecipeInstructions] = useState('');
  const [showCreateMenuItemModal, setShowCreateMenuItemModal] = useState(false);
  const [showCreateKitchenOrderModal, setShowCreateKitchenOrderModal] = useState(false);
  const [newMenuItemName, setNewMenuItemName] = useState('');
  const [newMenuItemDesc, setNewMenuItemDesc] = useState('');
  const [newMenuItemPrice, setNewMenuItemPrice] = useState('');
  const [newKoStayId, setNewKoStayId] = useState('');
  const [newKoNotes, setNewKoNotes] = useState('');
  const [newKoMealIncluded, setNewKoMealIncluded] = useState(false);
  const [newKoItems, setNewKoItems] = useState<{ kitchen_item_id: string, quantity: string }[]>([{ kitchen_item_id: '', quantity: '1' }]);
  const [kitchenSubTab, setKitchenSubTab] = useState<'orders' | 'menu' | 'costing'>('orders');

  // Phase 9 Employees State
  const [employees, setEmployees] = useState<any[]>([]);
  const [departments, setDepartments] = useState<any[]>([]);
  const [shifts, setShifts] = useState<any[]>([]);
  const [attendanceList, setAttendanceList] = useState<any[]>([]);
  const [attendanceDate, setAttendanceDate] = useState(new Date().toISOString().split('T')[0]);
  const [showCreateEmployeeModal, setShowCreateEmployeeModal] = useState(false);
  const [showCreateDeptModal, setShowCreateDeptModal] = useState(false);
  const [showCreateShiftModal, setShowCreateShiftModal] = useState(false);
  const [empFirstName, setEmpFirstName] = useState('');
  const [empLastName, setEmpLastName] = useState('');
  const [empCode, setEmpCode] = useState('');
  const [empEmail, setEmpEmail] = useState('');
  const [empPhone, setEmpPhone] = useState('');
  const [empDeptId, setEmpDeptId] = useState('');
  const [empShiftId, setEmpShiftId] = useState('');
  const [empEmergencyName, setEmpEmergencyName] = useState('');
  const [empEmergencyPhone, setEmpEmergencyPhone] = useState('');
  const [empSalary, setEmpSalary] = useState('');
  const [empStatus, setEmpStatus] = useState('Active');
  const [deptName, setDeptName] = useState('');
  const [deptCode, setDeptCode] = useState('');
  const [shiftName, setShiftName] = useState('');
  const [shiftStart, setShiftStart] = useState('');
  const [shiftEnd, setShiftEnd] = useState('');
  const [empSubTab, setEmpSubTab] = useState<'roster' | 'attendance' | 'settings'>('roster');

  // Phase 10 Reports state
  const [reportSubTab, setReportSubTab] = useState<'collection' | 'occupancy' | 'gst' | 'revenue'>('collection');
  const [occupancyReport, setOccupancyReport] = useState<any[]>([]);
  const [gstReport, setGstReport] = useState<any | null>(null);
  const [revenueReport, setRevenueReport] = useState<any[]>([]);

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

      // Fetch invoices
      const invRes = await fetch(`/api/hotels/${activeHotelId}/invoices`, { headers: getHeaders() });
      const invData = await invRes.json();
      if (invData.success) setInvoices(invData.data);

      // Fetch messages queue logs
      const msgRes = await fetch(`/api/hotels/${activeHotelId}/message-queue`, { headers: getHeaders() });
      const msgData = await msgRes.json();
      if (msgData.success) setMessages(msgData.data);

       // Fetch collections report metrics
       const colRes = await fetch(`/api/hotels/${activeHotelId}/reports/collection?start_date=${colStartDate}&end_date=${colEndDate}`, { headers: getHeaders() });
       const colData = await colRes.json();
       if (colData.success) setCollections(colData.data);

       // Fetch occupancy metrics
       const occRes = await fetch(`/api/hotels/${activeHotelId}/reports/occupancy?start_date=${colStartDate}&end_date=${colEndDate}`, { headers: getHeaders() });
       const occData = await occRes.json();
       if (occData.success) setOccupancyReport(occData.data);

       // Fetch GST tax breakdown
       const gstReportRes = await fetch(`/api/hotels/${activeHotelId}/reports/gst?start_date=${colStartDate}&end_date=${colEndDate}`, { headers: getHeaders() });
       const gstReportData = await gstReportRes.json();
       if (gstReportData.success) setGstReport(gstReportData.data);

       // Fetch Revenue metrics
       const revRes = await fetch(`/api/hotels/${activeHotelId}/reports/revenue?start_date=${colStartDate}&end_date=${colEndDate}`, { headers: getHeaders() });
       const revData = await revRes.json();
       if (revData.success) setRevenueReport(revData.data);

      // Fetch housekeeping tasks
      let hkUrl = `/api/hotels/${activeHotelId}/housekeeping/tasks`;
      const hkQuery: string[] = [];
      if (hkStatusFilter) hkQuery.push(`status=${hkStatusFilter}`);
      if (hkTypeFilter) hkQuery.push(`task_type=${hkTypeFilter}`);
      if (hkQuery.length > 0) hkUrl += `?${hkQuery.join('&')}`;
      const hkRes = await fetch(hkUrl, { headers: getHeaders() });
      const hkData = await hkRes.json();
      if (hkData.success) setHousekeepingTasks(hkData.data);

      // Fetch inventory details
      const itemRes = await fetch(`/api/hotels/${activeHotelId}/inventory/items`, { headers: getHeaders() });
      const itemData = await itemRes.json();
      if (itemData.success) setInventoryItems(itemData.data);

      const vendRes = await fetch(`/api/hotels/${activeHotelId}/inventory/vendors`, { headers: getHeaders() });
      const vendData = await vendRes.json();
      if (vendData.success) setVendors(vendData.data);

      const poRes = await fetch(`/api/hotels/${activeHotelId}/purchases/orders`, { headers: getHeaders() });
      const poData = await poRes.json();
      if (poData.success) setPurchaseOrders(poData.data);

      const grnRes = await fetch(`/api/hotels/${activeHotelId}/purchases/receipts`, { headers: getHeaders() });
      const grnData = await grnRes.json();
      if (grnData.success) setGoodsReceipts(grnData.data);

      // Fetch kitchen details
      const kRes = await fetch(`/api/hotels/${activeHotelId}/kitchen/menu-items`, { headers: getHeaders() });
      const kData = await kRes.json();
      if (kData.success) setKitchenItems(kData.data);

      const ordRes = await fetch(`/api/hotels/${activeHotelId}/kitchen/orders`, { headers: getHeaders() });
      const ordData = await ordRes.json();
      if (ordData.success) setKitchenOrders(ordData.data);

      const costRes = await fetch(`/api/hotels/${activeHotelId}/kitchen/costing-sheet`, { headers: getHeaders() });
      const costData = await costRes.json();
      if (costData.success) setKitchenCostingSheet(costData.data);

      // Fetch employee module details
      const empRes = await fetch(`/api/hotels/${activeHotelId}/employees`, { headers: getHeaders() });
      const empData = await empRes.json();
      if (empData.success) setEmployees(empData.data);

      const deptRes = await fetch(`/api/hotels/${activeHotelId}/employees/departments`, { headers: getHeaders() });
      const deptData = await deptRes.json();
      if (deptData.success) setDepartments(deptData.data);

      const shiftRes = await fetch(`/api/hotels/${activeHotelId}/employees/shifts`, { headers: getHeaders() });
      const shiftData = await shiftRes.json();
      if (shiftData.success) setShifts(shiftData.data);

      const attRes = await fetch(`/api/hotels/${activeHotelId}/employees/attendance?date=${attendanceDate}`, { headers: getHeaders() });
      const attData = await attRes.json();
      if (attData.success) setAttendanceList(attData.data);

    } catch (e) {
      showFeedback('danger', 'Failed to retrieve hotel-specific metadata.');
    }
  };

  const fetchAttendanceForDate = async (date: string) => {
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/employees/attendance?date=${date}`, { headers: getHeaders() });
      const data = await res.json();
      if (data.success) {
        setAttendanceList(data.data);
      }
    } catch (e) {
      // Ignored gracefully in background
    }
  };

  useEffect(() => {
    if (token && activeHotelId > 0 && attendanceDate) {
      fetchAttendanceForDate(attendanceDate);
    }
  }, [token, activeHotelId, attendanceDate]);

  useEffect(() => {
    if (token) {
      fetchAllInitialData();
    }
  }, [token]);

  useEffect(() => {
    if (token && activeHotelId > 0) {
      fetchScopedData();
    }
  }, [token, activeHotelId, activeTab, guestSearch, colStartDate, colEndDate, hkStatusFilter, hkTypeFilter]);

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

  // Phase 5 Action Handlers
  const handleFetchVerifLogs = async (doc: any) => {
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/guests/${selectedGuest.id}/documents/${doc.id}/verification-logs`, {
        headers: getHeaders()
      });
      const data = await res.json();
      if (data.success) {
        setVerifLogs(data.data);
        setShowLogsModal(true);
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to retrieve verification logs.');
    }
  };

  const handleRequestOtp = async (doc: any) => {
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/guests/${selectedGuest.id}/documents/${doc.id}/verify/otp-request`, {
        method: 'POST',
        headers: getHeaders()
      });
      const data = await res.json();
      if (data.success) {
        setVerifyingDoc(doc);
        setOtpClientId(data.data.client_id);
        setOtpCode('');
        setShowOtpModal(true);
        showFeedback('success', data.message);
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to request verification OTP.');
    }
  };

  const handleVerifyOtpSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!verifyingDoc) return;
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/guests/${selectedGuest.id}/documents/${verifyingDoc.id}/verify/otp-submit`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          otp: otpCode,
          client_id: otpClientId
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        setShowOtpModal(false);
        setVerifyingDoc(null);
        // Refresh documents list
        const docRes = await fetch(`/api/hotels/${activeHotelId}/guests/${selectedGuest.id}/documents`, { headers: getHeaders() });
        const docData = await docRes.json();
        if (docData.success) setGuestDocs(docData.data);
        // Refresh guest details
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to verify OTP.');
    }
  };

  const handleManualFallbackSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!verifyingDoc) return;
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/guests/${selectedGuest.id}/documents/${verifyingDoc.id}/verify/manual-fallback`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          reason: fallbackReason
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        setShowFallbackModal(false);
        setVerifyingDoc(null);
        setFallbackReason('');
        // Refresh documents list
        const docRes = await fetch(`/api/hotels/${activeHotelId}/guests/${selectedGuest.id}/documents`, { headers: getHeaders() });
        const docData = await docRes.json();
        if (docData.success) setGuestDocs(docData.data);
        // Refresh guest details
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to apply manual override.');
    }
  };

  // Phase 6 Action Handlers
  const fetchHousekeepingTasks = async () => {
    if (!activeHotelId) return;
    let url = `/api/hotels/${activeHotelId}/housekeeping/tasks`;
    const query: string[] = [];
    if (hkStatusFilter) query.push(`status=${hkStatusFilter}`);
    if (hkTypeFilter) query.push(`task_type=${hkTypeFilter}`);
    if (query.length > 0) url += `?${query.join('&')}`;

    try {
      const res = await fetch(url, { headers: getHeaders() });
      const data = await res.json();
      if (data.success) {
        setHousekeepingTasks(data.data);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to retrieve housekeeping tasks.');
    }
  };

  const handleCreateHkTask = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/housekeeping/tasks`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          room_id: newHkRoomId || null,
          task_type: newHkType,
          priority: newHkPriority,
          assigned_to: newHkAssignee || null,
          notes: newHkNotes
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        setNewHkRoomId('');
        setNewHkNotes('');
        setNewHkAssignee('');
        fetchHousekeepingTasks();
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to log housekeeping task.');
    }
  };

  const handleAssignTask = async (taskId: number, assigneeId: string) => {
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/housekeeping/tasks/${taskId}/assign`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          assigned_to: assigneeId || null
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        fetchHousekeepingTasks();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to assign staff.');
    }
  };

  const handleUpdateHkStatus = async (taskId: number, status: string) => {
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/housekeeping/tasks/${taskId}/status`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          status
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        fetchHousekeepingTasks();
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to update task status.');
    }
  };

  // Phase 7 Action Handlers
  const handleCreateInventoryItem = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/inventory/items`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          sku: newItemSku,
          name: newItemName,
          category: newItemCategory,
          unit_of_measure: newItemUom,
          min_stock_level: parseFloat(newItemMinStock)
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        setNewItemSku('');
        setNewItemName('');
        setNewItemCategory('');
        setNewItemMinStock('0');
        setShowCreateItemModal(false);
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to register item.');
    }
  };

  const handleCreateVendor = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/inventory/vendors`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          name: newVendorName,
          contact_name: newVendorContact,
          phone: newVendorPhone,
          email: newVendorEmail,
          address: newVendorAddress,
          gst_number: newVendorGst
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        setNewVendorName('');
        setNewVendorContact('');
        setNewVendorPhone('');
        setNewVendorEmail('');
        setNewVendorAddress('');
        setNewVendorGst('');
        setShowCreateVendorModal(false);
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to register vendor.');
    }
  };

  const handleCreatePoSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/purchases/orders`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          vendor_id: newPoVendorId,
          notes: newPoNotes,
          items: newPoItems
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        setNewPoVendorId('');
        setNewPoNotes('');
        setNewPoItems([{ inventory_item_id: '', quantity: '', unit_price: '' }]);
        setShowCreatePoModal(false);
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to generate PO.');
    }
  };

  const handleApprovePo = async (poId: number, status: 'Approved' | 'Rejected') => {
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/purchases/orders/${poId}/approve`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({ status })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to update PO status.');
    }
  };

  const handleFetchItemLedger = async (item: any) => {
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/inventory/items/${item.id}/ledger`, {
        headers: getHeaders()
      });
      const data = await res.json();
      if (data.success) {
        setSelectedHkItem(item);
        setItemLedger(data.data);
        setShowLedgerModal(true);
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to load ledger.');
    }
  };

  const handleAdjustStockSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedHkItem) return;
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/inventory/items/${selectedHkItem.id}/adjust`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          quantity: parseFloat(adjustQty),
          unit_cost: parseFloat(adjustCost || '0'),
          reason: adjustReason
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        setAdjustQty('');
        setAdjustCost('');
        setAdjustReason('');
        setShowAdjustModal(false);
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to adjust stock.');
    }
  };

  const handleOpenGrnModal = (po: any) => {
    setSelectedPo(po);
    // Initialize received values to PO targets for convenience
    const items = po.items.map((item: any) => ({
      inventory_item_id: item.inventory_item_id,
      item_name: item.item_name,
      quantity: item.quantity,
      unit_cost: item.unit_price,
      batch_number: '',
      expiry_date: ''
    }));
    setGrnItems(items);
    setGrnNotes('');
    setShowGrnModal(true);
  };

  const handleGrnSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedPo) return;
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/purchases/orders/${selectedPo.id}/receive`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          notes: grnNotes,
          items: grnItems
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        setShowGrnModal(false);
        setSelectedPo(null);
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to log Goods Receipt.');
    }
  };

  // Phase 8 Action Handlers
  const handleCreateMenuItem = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/kitchen/menu-items`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          name: newMenuItemName,
          description: newMenuItemDesc,
          price: parseFloat(newMenuItemPrice)
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        setNewMenuItemName('');
        setNewMenuItemDesc('');
        setNewMenuItemPrice('');
        setShowCreateMenuItemModal(false);
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to save menu item.');
    }
  };

  const handleConfigureRecipeSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedKItem) return;
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/kitchen/menu-items/${selectedKItem.id}/recipe`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          instructions: recipeInstructions,
          ingredients: recipeIngredients
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        setShowRecipeModal(false);
        setSelectedKItem(null);
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to save recipe.');
    }
  };

  const handleCreateKitchenOrder = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/kitchen/orders`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          stay_id: parseInt(newKoStayId),
          notes: newKoNotes,
          is_meal_plan_included: newKoMealIncluded,
          items: newKoItems
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        setNewKoStayId('');
        setNewKoNotes('');
        setNewKoMealIncluded(false);
        setNewKoItems([{ kitchen_item_id: '', quantity: '1' }]);
        setShowCreateKitchenOrderModal(false);
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to send kitchen order.');
    }
  };

  const handleUpdateKitchenOrderStatus = async (orderId: number, status: string) => {
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/kitchen/orders/${orderId}/status`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({ status })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to update order status.');
    }
  };

  // Phase 9 Employees Action Handlers
  const handleCreateEmployee = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const payload: any = {
        first_name: empFirstName,
        last_name: empLastName,
        employee_code: empCode,
        email: empEmail || null,
        phone: empPhone || null,
        department_id: empDeptId ? parseInt(empDeptId) : null,
        shift_id: empShiftId ? parseInt(empShiftId) : null,
        emergency_contact_name: empEmergencyName || null,
        emergency_contact_phone: empEmergencyPhone || null,
        status: empStatus
      };
      if (empSalary) {
        payload.salary_base = parseFloat(empSalary);
      }

      const res = await fetch(`/api/hotels/${activeHotelId}/employees`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        setEmpFirstName('');
        setEmpLastName('');
        setEmpCode('');
        setEmpEmail('');
        setEmpPhone('');
        setEmpDeptId('');
        setEmpShiftId('');
        setEmpEmergencyName('');
        setEmpEmergencyPhone('');
        setEmpSalary('');
        setEmpStatus('Active');
        setShowCreateEmployeeModal(false);
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to register employee.');
    }
  };

  const handleCreateDepartment = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/employees/departments`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          name: deptName,
          code: deptCode
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        setDeptName('');
        setDeptCode('');
        setShowCreateDeptModal(false);
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to create department.');
    }
  };

  const handleCreateShift = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/employees/shifts`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          name: shiftName,
          start_time: shiftStart,
          end_time: shiftEnd
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        setShiftName('');
        setShiftStart('');
        setShiftEnd('');
        setShowCreateShiftModal(false);
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to create shift.');
    }
  };

  const handleClockEmployee = async (employeeId: number, action: 'in' | 'out') => {
    try {
      const res = await fetch(`/api/hotels/${activeHotelId}/employees/attendance/clock`, {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
          employee_id: employeeId,
          action
        })
      });
      const data = await res.json();
      if (data.success) {
        showFeedback('success', data.message);
        fetchScopedData();
      } else {
        showFeedback('danger', data.message);
      }
    } catch (e) {
      showFeedback('danger', 'Failed to update time clock.');
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
          <li className="nav-item">
            <button
              onClick={() => {
                setActiveTab('invoices');
                setSelectedInvoice(null);
              }}
              className={`nav-button ${activeTab === 'invoices' ? 'active' : ''}`}
            >
              Invoices
            </button>
          </li>
          <li className="nav-item">
            <button
              onClick={() => {
                setActiveTab('reports');
              }}
              className={`nav-button ${activeTab === 'reports' ? 'active' : ''}`}
            >
              Financial Reports
            </button>
          </li>
          <li className="nav-item">
            <button
              onClick={() => {
                setActiveTab('notifications');
              }}
              className={`nav-button ${activeTab === 'notifications' ? 'active' : ''}`}
            >
              Message Queue Logs
            </button>
          </li>
          <li className="nav-item">
            <button
              onClick={() => {
                setActiveTab('housekeeping');
              }}
              className={`nav-button ${activeTab === 'housekeeping' ? 'active' : ''}`}
            >
              Housekeeping & Readiness
            </button>
          </li>
          <li className="nav-item">
            <button
              onClick={() => {
                setActiveTab('inventory');
              }}
              className={`nav-button ${activeTab === 'inventory' ? 'active' : ''}`}
            >
              Inventory & Purchases
            </button>
          </li>
          <li className="nav-item">
            <button
              onClick={() => {
                setActiveTab('kitchen');
              }}
              className={`nav-button ${activeTab === 'kitchen' ? 'active' : ''}`}
            >
              Kitchen Room Service
            </button>
          </li>
          <li className="nav-item">
            <button
              onClick={() => {
                setActiveTab('employees');
              }}
              className={`nav-button ${activeTab === 'employees' ? 'active' : ''}`}
            >
              Employees & Attendance
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
                            
                            {/* Verification Status Badge */}
                            <span style={{ 
                              marginLeft: '8px',
                              padding: '2px 8px',
                              borderRadius: '12px',
                              fontSize: '11px',
                              fontWeight: 'bold',
                              color: '#fff',
                              background: doc.verification_status === 'verified' ? 'var(--status-available)' :
                                          doc.verification_status === 'manual_fallback' ? '#d97706' :
                                          doc.verification_status === 'pending' ? 'var(--primary)' :
                                          doc.verification_status === 'failed' ? 'var(--status-occupied)' : '#9ca3af'
                            }}>
                              {doc.verification_status === 'verified' ? 'Verified (OTP)' :
                               doc.verification_status === 'manual_fallback' ? 'Verified (Manual)' :
                               doc.verification_status === 'pending' ? 'Pending OTP' :
                               doc.verification_status === 'failed' ? 'Verification Failed' : 'Not Verified'}
                            </span>

                            {doc.file_path && (
                              <div style={{ marginTop: '4px', fontSize: '12px' }}>
                                <a href={doc.file_path} target="_blank" rel="noopener noreferrer" style={{ color: 'var(--primary)', textDecoration: 'underline' }}>View Uploaded File</a>
                              </div>
                            )}
                          </div>
                          <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                            <button onClick={() => handleDecryptDoc(doc.id)} className="btn btn-secondary btn-sm" style={{ padding: '4px 8px', fontSize: '11px' }}>Decrypt</button>
                            
                            {doc.document_type === 'Aadhaar' && doc.verification_status !== 'verified' && doc.verification_status !== 'manual_fallback' && (
                              <>
                                <button onClick={() => handleRequestOtp(doc)} className="btn btn-sm" style={{ padding: '4px 8px', fontSize: '11px', background: 'var(--primary)', color: '#fff' }}>Verify Aadhaar</button>
                                <button onClick={() => {
                                  setVerifyingDoc(doc);
                                  setFallbackReason('');
                                  setShowFallbackModal(true);
                                }} className="btn btn-secondary btn-sm" style={{ padding: '4px 8px', fontSize: '11px', borderColor: '#d97706', color: '#d97706' }}>Manual Override</button>
                              </>
                            )}
                            
                            <button onClick={() => handleFetchVerifLogs(doc)} className="btn btn-secondary btn-sm" style={{ padding: '4px 8px', fontSize: '11px' }}>Logs</button>
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
                        <h4 style={{ margin: '0 0 8px 0', fontSize: '14px' }}>Collect Stay Payment (Split/Single)</h4>
                        {splitPayments.map((p, idx) => (
                          <div key={idx} style={{ display: 'flex', gap: '4px', marginBottom: '8px' }}>
                            <select className="select-dropdown select-dropdown-sm" style={{ flex: 1, height: '32px', fontSize: '12px' }} value={p.method} onChange={(e) => {
                              const updated = [...splitPayments];
                              updated[idx].method = e.target.value;
                              setSplitPayments(updated);
                            }}>
                              <option value="Cash">Cash</option>
                              <option value="Card">Card</option>
                              <option value="UPI">UPI</option>
                              <option value="Bank">Bank Transfer</option>
                            </select>
                            <input type="number" className="form-input form-input-sm" style={{ flex: 1.2, height: '32px', fontSize: '12px', padding: '4px' }} placeholder="Amount" value={p.amount} onChange={(e) => {
                              const updated = [...splitPayments];
                              updated[idx].amount = e.target.value;
                              setSplitPayments(updated);
                            }} required />
                            <input type="text" className="form-input form-input-sm" style={{ flex: 1, height: '32px', fontSize: '12px', padding: '4px' }} placeholder="Ref" value={p.ref} onChange={(e) => {
                              const updated = [...splitPayments];
                              updated[idx].ref = e.target.value;
                              setSplitPayments(updated);
                            }} />
                            {splitPayments.length > 1 && (
                              <button type="button" onClick={() => setSplitPayments(splitPayments.filter((_, i) => i !== idx))} style={{ background: '#ef4444', color: '#fff', border: 'none', padding: '0 8px', borderRadius: '4px', cursor: 'pointer' }}>×</button>
                            )}
                          </div>
                        ))}
                        <div style={{ display: 'flex', gap: '6px', marginTop: '10px' }}>
                          <button type="button" onClick={() => setSplitPayments([...splitPayments, { method: 'Cash', amount: '', ref: '' }])} className="btn btn-sm btn-secondary" style={{ flex: 1, fontSize: '11px', padding: '4px' }}>+ Split Method</button>
                          <button type="button" onClick={async () => {
                            let successCount = 0;
                            let errors = [];
                            for (const p of splitPayments) {
                              const amt = parseFloat(p.amount);
                              if (isNaN(amt) || amt <= 0) continue;
                              try {
                                const res = await fetch(`/api/hotels/${activeHotelId}/stays/${selectedStay.id}/payments`, {
                                  method: 'POST',
                                  headers: getHeaders(),
                                  body: JSON.stringify({
                                    payment_method: p.method,
                                    amount: amt,
                                    transaction_reference: p.ref
                                  })
                                });
                                const data = await res.json();
                                if (data.success) {
                                  successCount++;
                                } else {
                                  errors.push(data.message);
                                }
                              } catch (e) {
                                errors.push('Connection error');
                              }
                            }
                            if (successCount > 0) {
                              showFeedback('success', `Applied ${successCount} payment allocations.`);
                              setSplitPayments([{ method: 'Cash', amount: '', ref: '' }]);
                              // Refresh selected stay
                              const stayRes = await fetch(`/api/hotels/${activeHotelId}/stays/${selectedStay.id}`, { headers: getHeaders() });
                              const stayData = await stayRes.json();
                              if (stayData.success) setSelectedStay(stayData.data);
                            }
                            if (errors.length > 0) {
                              showFeedback('danger', `Errors: ${errors.join(', ')}`);
                            }
                          }} className="btn btn-sm" style={{ flex: 1.5, background: 'var(--status-available)', color: '#fff', fontSize: '11px', padding: '4px' }}>Apply Payments</button>
                        </div>
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

          {/* 9. INVOICES DIRECTORY */}
          {activeTab === 'invoices' && (
            <div className="glass-panel">
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' }}>
                <h2 className="page-title" style={{ margin: 0 }}>GST Invoices Directory</h2>
              </div>
              <div className="table-wrapper">
                <table className="table">
                  <thead>
                    <tr>
                      <th>Invoice No.</th>
                      <th>Guest Name</th>
                      <th>Subtotal</th>
                      <th>CGST/SGST</th>
                      <th>Total Amount</th>
                      <th>Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    {invoices.map((inv) => (
                      <tr key={inv.id}>
                        <td><strong>{inv.invoice_number}</strong></td>
                        <td>{inv.first_name} {inv.last_name}</td>
                        <td>{parseFloat(inv.subtotal).toFixed(2)} INR</td>
                        <td>
                          {parseFloat(inv.cgst).toFixed(2)} / {parseFloat(inv.sgst).toFixed(2)}
                        </td>
                        <td><strong>{parseFloat(inv.total_amount).toFixed(2)} INR</strong></td>
                        <td>
                          <span className={`room-status-badge`} style={{ margin: 0, background: inv.status === 'Paid' ? 'var(--status-available)' : 'var(--status-occupied)', color: '#fff' }}>
                            {inv.status}
                          </span>
                        </td>
                        <td>
                          <button onClick={async () => {
                            const res = await fetch(`/api/hotels/${activeHotelId}/invoices/${inv.id}`, { headers: getHeaders() });
                            const data = await res.json();
                            if (data.success) {
                              setSelectedInvoice(data.data);
                            }
                          }} className="btn btn-secondary btn-sm" style={{ padding: '6px 10px', fontSize: '12px' }}>Print / View</button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* 10. FINANCIAL & OPERATIONAL REPORTS */}
          {activeTab === 'reports' && (
            <div className="glass-panel">
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px', flexWrap: 'wrap', gap: '10px' }}>
                <h2 className="page-title" style={{ margin: 0 }}>Hotel Analytics & Reports</h2>
                
                {/* Reports Sub tabs */}
                <div style={{ display: 'flex', gap: '8px' }}>
                  <button onClick={() => setReportSubTab('collection')} className={`btn ${reportSubTab === 'collection' ? '' : 'btn-secondary'}`} style={{ fontSize: '13px', padding: '6px 12px' }}>Collections</button>
                  <button onClick={() => setReportSubTab('occupancy')} className={`btn ${reportSubTab === 'occupancy' ? '' : 'btn-secondary'}`} style={{ fontSize: '13px', padding: '6px 12px' }}>Occupancy Rates</button>
                  <button onClick={() => setReportSubTab('gst')} className={`btn ${reportSubTab === 'gst' ? '' : 'btn-secondary'}`} style={{ fontSize: '13px', padding: '6px 12px' }}>GST Tax Ledger</button>
                  <button onClick={() => setReportSubTab('revenue')} className={`btn ${reportSubTab === 'revenue' ? '' : 'btn-secondary'}`} style={{ fontSize: '13px', padding: '6px 12px' }}>Revenue Centers</button>
                </div>
              </div>

              {/* Filters */}
              <div className="form-grid" style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '15px', marginBottom: '25px', alignItems: 'end' }}>
                <div className="form-group">
                  <label className="form-label">Start Date</label>
                  <input type="date" className="form-input" value={colStartDate} onChange={(e) => setColStartDate(e.target.value)} />
                </div>
                <div className="form-group">
                  <label className="form-label">End Date</label>
                  <input type="date" className="form-input" value={colEndDate} onChange={(e) => setColEndDate(e.target.value)} />
                </div>
                <button onClick={fetchScopedData} className="btn" style={{ height: '40px' }}>Filter Reports</button>
              </div>

              {/* COLLECTIONS SUBTAB */}
              {reportSubTab === 'collection' && (
                <div>
                  <h3 style={{ fontSize: '16px', marginBottom: '15px' }}>Daily Financial Collections</h3>
                  {(() => {
                    let grandTotal = 0;
                    let methodTotals: { [key: string]: number } = {};
                    collections.forEach(day => {
                      grandTotal += day.day_total;
                      day.methods.forEach((m: any) => {
                        methodTotals[m.payment_method] = (methodTotals[m.payment_method] || 0) + m.total_amount;
                      });
                    });
                    return (
                      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))', gap: '15px', marginBottom: '25px' }}>
                        <div style={{ background: 'rgba(157, 59, 248, 0.1)', border: '1px solid var(--primary)', padding: '15px', borderRadius: '8px', textAlign: 'center' }}>
                          <span style={{ fontSize: '11px', color: '#a78bfa', textTransform: 'uppercase', fontWeight: 'bold' }}>Total Collections</span>
                          <h3 style={{ margin: '8px 0 0 0', fontSize: '20px' }}>{grandTotal.toFixed(2)} INR</h3>
                        </div>
                        {['UPI', 'Cash', 'Card', 'Bank'].map(method => (
                          <div key={method} style={{ background: 'rgba(255,255,255,0.05)', border: '1px solid rgba(255,255,255,0.1)', padding: '15px', borderRadius: '8px', textAlign: 'center' }}>
                            <span style={{ fontSize: '11px', color: '#9ca3af', textTransform: 'uppercase' }}>{method}</span>
                            <h3 style={{ margin: '8px 0 0 0', fontSize: '18px' }}>{(methodTotals[method] || 0).toFixed(2)}</h3>
                          </div>
                        ))}
                      </div>
                    );
                  })()}

                  <div className="table-wrapper">
                    <table className="table">
                      <thead>
                        <tr>
                          <th>Date</th>
                          <th>Payment Breakdown</th>
                          <th style={{ textAlign: 'right' }}>Total Collections</th>
                        </tr>
                      </thead>
                      <tbody>
                        {collections.length === 0 ? (
                          <tr>
                            <td colSpan={3} style={{ textAlign: 'center', color: '#9ca3af', fontStyle: 'italic' }}>No collections found.</td>
                          </tr>
                        ) : (
                          collections.map((col) => (
                            <tr key={col.date}>
                              <td><strong>{col.date}</strong></td>
                              <td>
                                <div style={{ display: 'flex', gap: '15px', fontSize: '13px' }}>
                                  {col.methods.map((m: any) => (
                                    <span key={m.payment_method}>
                                      <strong style={{ color: '#a78bfa' }}>{m.payment_method}:</strong> {m.total_amount.toFixed(2)} ({m.transaction_count} tx)
                                    </span>
                                  ))}
                                </div>
                              </td>
                              <td style={{ textAlign: 'right', fontWeight: 'bold', color: 'var(--status-available)' }}>
                                {col.day_total.toFixed(2)} INR
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {/* OCCUPANCY SUBTAB */}
              {reportSubTab === 'occupancy' && (
                <div>
                  <h3 style={{ fontSize: '16px', marginBottom: '15px' }}>Room Occupancy Rates</h3>
                  
                  {/* Summary card */}
                  {(() => {
                    let totalDays = occupancyReport.length;
                    let avgRate = totalDays > 0 ? occupancyReport.reduce((acc, curr) => acc + curr.occupancy_rate, 0) / totalDays : 0;
                    return (
                      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '15px', marginBottom: '25px' }}>
                        <div style={{ background: 'rgba(16, 185, 129, 0.1)', border: '1px solid #10b981', padding: '15px', borderRadius: '8px', textAlign: 'center' }}>
                          <span style={{ fontSize: '11px', color: '#34d399', textTransform: 'uppercase', fontWeight: 'bold' }}>Average Occupancy Rate</span>
                          <h3 style={{ margin: '8px 0 0 0', fontSize: '22px' }}>{avgRate.toFixed(1)}%</h3>
                        </div>
                        <div style={{ background: 'rgba(255,255,255,0.05)', border: '1px solid rgba(255,255,255,0.1)', padding: '15px', borderRadius: '8px', textAlign: 'center' }}>
                          <span style={{ fontSize: '11px', color: '#9ca3af', textTransform: 'uppercase' }}>Scope Days Analyzed</span>
                          <h3 style={{ margin: '8px 0 0 0', fontSize: '22px' }}>{totalDays} days</h3>
                        </div>
                      </div>
                    );
                  })()}

                  <div className="table-wrapper">
                    <table className="table">
                      <thead>
                        <tr>
                          <th>Date</th>
                          <th>Total Room Count</th>
                          <th>Occupied Room Count</th>
                          <th style={{ textAlign: 'right' }}>Occupancy Percentage</th>
                        </tr>
                      </thead>
                      <tbody>
                        {occupancyReport.length === 0 ? (
                          <tr>
                            <td colSpan={4} style={{ textAlign: 'center', color: '#9ca3af', fontStyle: 'italic' }}>No occupancy records analyzed.</td>
                          </tr>
                        ) : (
                          occupancyReport.map((row) => (
                            <tr key={row.date}>
                              <td><strong>{row.date}</strong></td>
                              <td>{row.total_rooms} rooms</td>
                              <td>
                                <span style={{ fontWeight: 'bold', color: row.occupied_rooms > 0 ? 'var(--status-available)' : '#cbd5e1' }}>
                                  {row.occupied_rooms} rooms
                                </span>
                              </td>
                              <td style={{ textAlign: 'right', fontWeight: 'bold' }}>
                                <span style={{ color: row.occupancy_rate >= 50 ? 'var(--status-available)' : '#cbd5e1' }}>
                                  {parseFloat(row.occupancy_rate).toFixed(1)}%
                                </span>
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {/* GST TAX LEDGER SUBTAB */}
              {reportSubTab === 'gst' && (
                <div>
                  <h3 style={{ fontSize: '16px', marginBottom: '15px' }}>GST Tax Ledger & Settlements</h3>
                  
                  {gstReport && gstReport.totals && (
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr 1fr', gap: '15px', marginBottom: '25px' }}>
                      <div style={{ background: 'rgba(255,255,255,0.05)', border: '1px solid rgba(255,255,255,0.1)', padding: '15px', borderRadius: '8px', textAlign: 'center' }}>
                        <span style={{ fontSize: '11px', color: '#9ca3af', textTransform: 'uppercase' }}>Taxable Sales (Net)</span>
                        <h3 style={{ margin: '8px 0 0 0', fontSize: '18px' }}>{parseFloat(gstReport.totals.total_taxable).toFixed(2)}</h3>
                      </div>
                      <div style={{ background: 'rgba(255,255,255,0.05)', border: '1px solid rgba(255,255,255,0.1)', padding: '15px', borderRadius: '8px', textAlign: 'center' }}>
                        <span style={{ fontSize: '11px', color: '#9ca3af', textTransform: 'uppercase' }}>CGST (Central Tax)</span>
                        <h3 style={{ margin: '8px 0 0 0', fontSize: '18px' }}>{parseFloat(gstReport.totals.total_cgst).toFixed(2)}</h3>
                      </div>
                      <div style={{ background: 'rgba(255,255,255,0.05)', border: '1px solid rgba(255,255,255,0.1)', padding: '15px', borderRadius: '8px', textAlign: 'center' }}>
                        <span style={{ fontSize: '11px', color: '#9ca3af', textTransform: 'uppercase' }}>SGST (State Tax)</span>
                        <h3 style={{ margin: '8px 0 0 0', fontSize: '18px' }}>{parseFloat(gstReport.totals.total_sgst).toFixed(2)}</h3>
                      </div>
                      <div style={{ background: 'rgba(59, 130, 246, 0.1)', border: '1px solid #3b82f6', padding: '15px', borderRadius: '8px', textAlign: 'center' }}>
                        <span style={{ fontSize: '11px', color: '#60a5fa', textTransform: 'uppercase', fontWeight: 'bold' }}>Total GST Tax Collected</span>
                        <h3 style={{ margin: '8px 0 0 0', fontSize: '18px' }}>{parseFloat(gstReport.totals.total_tax).toFixed(2)}</h3>
                      </div>
                    </div>
                  )}

                  <div className="table-wrapper">
                    <table className="table">
                      <thead>
                        <tr>
                          <th>Revenue Center Type</th>
                          <th>Taxable Revenue</th>
                          <th>CGST (50%)</th>
                          <th>SGST (50%)</th>
                          <th style={{ textAlign: 'right' }}>Total Tax Amt</th>
                        </tr>
                      </thead>
                      <tbody>
                        {!gstReport || !gstReport.breakdown || gstReport.breakdown.length === 0 ? (
                          <tr>
                            <td colSpan={5} style={{ textAlign: 'center', color: '#9ca3af', fontStyle: 'italic' }}>No GST calculations for selected period.</td>
                          </tr>
                        ) : (
                          gstReport.breakdown.map((row: any) => (
                            <tr key={row.item_type}>
                              <td><strong style={{ textTransform: 'capitalize' }}>{row.item_type.replace('_', ' ')}</strong></td>
                              <td>{parseFloat(row.taxable_amount).toFixed(2)} INR</td>
                              <td>{parseFloat(row.cgst).toFixed(2)} INR</td>
                              <td>{parseFloat(row.sgst).toFixed(2)} INR</td>
                              <td style={{ textAlign: 'right', fontWeight: 'bold', color: 'var(--status-available)' }}>
                                {parseFloat(row.total_tax).toFixed(2)} INR
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {/* REVENUE CENTERS SUBTAB */}
              {reportSubTab === 'revenue' && (
                <div>
                  <h3 style={{ fontSize: '16px', marginBottom: '15px' }}>Revenue Center Performance</h3>
                  
                  {/* Summary card */}
                  {(() => {
                    let totalRev = revenueReport.reduce((acc, curr) => acc + curr.day_total, 0);
                    let centersBreakdown: { [key: string]: number } = {};
                    revenueReport.forEach(day => {
                      day.centers.forEach((c: any) => {
                        centersBreakdown[c.item_type] = (centersBreakdown[c.item_type] || 0) + c.amount;
                      });
                    });

                    return (
                      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '15px', marginBottom: '25px' }}>
                        <div style={{ background: 'rgba(59, 130, 246, 0.1)', border: '1px solid #3b82f6', padding: '15px', borderRadius: '8px', textAlign: 'center' }}>
                          <span style={{ fontSize: '11px', color: '#60a5fa', textTransform: 'uppercase', fontWeight: 'bold' }}>Total Gross Revenue</span>
                          <h3 style={{ margin: '8px 0 0 0', fontSize: '20px' }}>{totalRev.toFixed(2)} INR</h3>
                        </div>
                        {Object.keys(centersBreakdown).map(center => (
                          <div key={center} style={{ background: 'rgba(255,255,255,0.05)', border: '1px solid rgba(255,255,255,0.1)', padding: '15px', borderRadius: '8px', textAlign: 'center' }}>
                            <span style={{ fontSize: '11px', color: '#9ca3af', textTransform: 'capitalize' }}>{center.replace('_', ' ')}</span>
                            <h3 style={{ margin: '8px 0 0 0', fontSize: '18px' }}>{centersBreakdown[center].toFixed(2)}</h3>
                          </div>
                        ))}
                      </div>
                    );
                  })()}

                  <div className="table-wrapper">
                    <table className="table">
                      <thead>
                        <tr>
                          <th>Date</th>
                          <th>Revenue Centers Breakdown</th>
                          <th style={{ textAlign: 'right' }}>Total Revenue</th>
                        </tr>
                      </thead>
                      <tbody>
                        {revenueReport.length === 0 ? (
                          <tr>
                            <td colSpan={3} style={{ textAlign: 'center', color: '#9ca3af', fontStyle: 'italic' }}>No revenue records compiled.</td>
                          </tr>
                        ) : (
                          revenueReport.map((day) => (
                            <tr key={day.date}>
                              <td><strong>{day.date}</strong></td>
                              <td>
                                <div style={{ display: 'flex', gap: '15px', fontSize: '13px' }}>
                                  {day.centers.map((c: any) => (
                                    <span key={c.item_type}>
                                      <strong style={{ textTransform: 'capitalize', color: '#60a5fa' }}>{c.item_type.replace('_', ' ')}:</strong> {c.amount.toFixed(2)}
                                    </span>
                                  ))}
                                </div>
                              </td>
                              <td style={{ textAlign: 'right', fontWeight: 'bold', color: 'var(--status-available)' }}>
                                {day.day_total.toFixed(2)} INR
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* 11. MESSAGING NOTIFICATION LOGS */}
          {activeTab === 'notifications' && (
            <div className="glass-panel">
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' }}>
                <h2 className="page-title" style={{ margin: 0 }}>MSG91 SMS/WhatsApp Queue Logs</h2>
                <button onClick={fetchScopedData} className="btn btn-secondary btn-sm">Refresh Queue</button>
              </div>
              <div className="table-wrapper">
                <table className="table">
                  <thead>
                    <tr>
                      <th>Created At</th>
                      <th>Type</th>
                      <th>Channel</th>
                      <th>Recipient</th>
                      <th>Status</th>
                      <th>Retry</th>
                      <th>Logs/Response</th>
                    </tr>
                  </thead>
                  <tbody>
                    {messages.map((m) => (
                      <tr key={m.id}>
                        <td>{new Date(m.created_at).toLocaleString()}</td>
                        <td><span style={{ fontSize: '11px', textTransform: 'uppercase', color: '#a78bfa', fontWeight: '600' }}>{m.message_type}</span></td>
                        <td>{m.channel}</td>
                        <td><code>{m.recipient}</code></td>
                        <td>
                          <span className={`room-status-badge`} style={{ margin: 0, background: m.status === 'sent' ? 'var(--status-available)' : 'var(--status-occupied)', color: '#fff' }}>
                            {m.status}
                          </span>
                        </td>
                        <td>{m.retry_count} / 3</td>
                        <td style={{ fontSize: '12px', maxWidth: '300px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                          {m.error_message ? (
                            <span style={{ color: 'var(--status-occupied)' }}>Err: {m.error_message}</span>
                          ) : (
                            <span style={{ color: '#9ca3af' }}>{m.provider_response || 'No logs'}</span>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* 12. HOUSEKEEPING & MAINTENANCE DASHBOARD */}
          {activeTab === 'housekeeping' && (
            <div>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h2 className="page-title" style={{ margin: 0 }}>Housekeeping & Room Readiness</h2>
                <button onClick={fetchHousekeepingTasks} className="btn btn-secondary btn-sm">Refresh Dashboard</button>
              </div>

              {/* Dirty / Under-Maintenance Rooms Panel */}
              <div className="glass-panel" style={{ marginBottom: '20px' }}>
                <h3 className="page-title" style={{ fontSize: '18px', marginBottom: '15px' }}>Current Dirty or Maintenance Rooms</h3>
                {rooms.filter(r => r.status === 'Cleaning' || r.status === 'Maintenance').length === 0 ? (
                  <p style={{ color: '#9ca3af', fontStyle: 'italic', margin: 0 }}>All rooms are currently Available or Occupied. No rooms need servicing.</p>
                ) : (
                  <div style={{ display: 'flex', flexWrap: 'wrap', gap: '15px' }}>
                    {rooms.filter(r => r.status === 'Cleaning' || r.status === 'Maintenance').map(room => {
                      // Find active task for this room if any
                      const activeTask = housekeepingTasks.find(t => t.room_id === room.id && t.status !== 'completed' && t.status !== 'cancelled');
                      return (
                        <div key={room.id} style={{ 
                          flex: '1 1 220px', 
                          background: 'rgba(255,255,255,0.4)', 
                          border: '1px solid rgba(0,0,0,0.05)', 
                          borderRadius: '8px', 
                          padding: '15px',
                          display: 'flex',
                          flexDirection: 'column',
                          justifyContent: 'space-between'
                        }}>
                          <div>
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '10px' }}>
                              <strong style={{ fontSize: '16px' }}>Room {room.room_number}</strong>
                              <span style={{ 
                                padding: '2px 8px', 
                                borderRadius: '12px', 
                                fontSize: '11px', 
                                fontWeight: 'bold', 
                                color: '#fff',
                                background: room.status === 'Cleaning' ? 'var(--primary)' : '#d97706' 
                              }}>{room.status}</span>
                            </div>
                            <p style={{ fontSize: '13px', margin: '0 0 10px 0', color: '#e2e8f0' }}>
                              Type: {roomTypes.find(t => t.id === room.room_type_id)?.name || 'N/A'}
                            </p>
                            {activeTask ? (
                              <div style={{ fontSize: '12px', background: 'rgba(0,0,0,0.1)', padding: '8px', borderRadius: '4px', marginBottom: '10px' }}>
                                <div style={{ marginBottom: '4px' }}><strong>Task Type:</strong> <span style={{ textTransform: 'capitalize' }}>{activeTask.task_type}</span></div>
                                <div style={{ marginBottom: '4px' }}><strong>Status:</strong> <span style={{ textTransform: 'capitalize' }}>{activeTask.status}</span></div>
                                <div><strong>Assignee:</strong> {activeTask.assignee_name || 'Unassigned'}</div>
                              </div>
                            ) : (
                              <p style={{ fontSize: '12px', color: '#9ca3af', fontStyle: 'italic', margin: '0 0 10px 0' }}>No active cleaning/maintenance task found.</p>
                            )}
                          </div>
                          
                          {activeTask ? (
                            <button 
                              onClick={() => handleUpdateHkStatus(activeTask.id, 'completed')} 
                              className="btn btn-sm" 
                              style={{ width: '100%', padding: '6px', fontSize: '12px', background: 'var(--status-available)', color: '#fff' }}
                            >
                              Release & Mark Ready
                            </button>
                          ) : (
                            <button 
                              onClick={() => {
                                setNewHkRoomId(String(room.id));
                                setNewHkType(room.status === 'Cleaning' ? 'cleaning' : 'maintenance');
                                setNewHkPriority(room.status === 'Cleaning' ? 'medium' : 'high');
                                setNewHkNotes('Quick start task to release room');
                              }} 
                              className="btn btn-secondary btn-sm" 
                              style={{ width: '100%', padding: '6px', fontSize: '12px' }}
                            >
                              Create Task
                            </button>
                          )}
                        </div>
                      );
                    })}
                  </div>
                )}
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 320px', gap: '20px', alignItems: 'start' }}>
                
                {/* Tasks List */}
                <div className="glass-panel">
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' }}>
                    <h3 className="page-title" style={{ fontSize: '18px', margin: 0 }}>All Housekeeping & Maintenance Tasks</h3>
                    <div style={{ display: 'flex', gap: '8px' }}>
                      <select className="select-dropdown" style={{ padding: '4px 8px', fontSize: '12px' }} value={hkTypeFilter} onChange={(e) => setHkTypeFilter(e.target.value)}>
                        <option value="">All Types</option>
                        <option value="cleaning">Cleaning</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="laundry">Laundry</option>
                      </select>
                      <select className="select-dropdown" style={{ padding: '4px 8px', fontSize: '12px' }} value={hkStatusFilter} onChange={(e) => setHkStatusFilter(e.target.value)}>
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                      </select>
                    </div>
                  </div>

                  <div className="table-wrapper">
                    <table className="table">
                      <thead>
                        <tr>
                          <th>Room</th>
                          <th>Type</th>
                          <th>Priority</th>
                          <th>Status</th>
                          <th>Assignee / Staff</th>
                          <th>Notes</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {housekeepingTasks.length === 0 ? (
                          <tr>
                            <td colSpan={7} style={{ textAlign: 'center', color: '#9ca3af', fontStyle: 'italic' }}>No tasks match filters.</td>
                          </tr>
                        ) : (
                          housekeepingTasks.map(task => (
                            <tr key={task.id}>
                              <td><strong>{task.room_number ? `Room ${task.room_number}` : 'Facility'}</strong></td>
                              <td><span style={{ fontSize: '11px', textTransform: 'uppercase', color: '#a78bfa', fontWeight: '600' }}>{task.task_type}</span></td>
                              <td>
                                <span style={{ 
                                  fontSize: '11px', 
                                  color: task.priority === 'high' ? 'var(--status-occupied)' : task.priority === 'medium' ? '#d97706' : '#9ca3af', 
                                  fontWeight: 'bold', 
                                  textTransform: 'uppercase' 
                                }}>{task.priority}</span>
                              </td>
                              <td>
                                <span className="room-status-badge" style={{ 
                                  margin: 0, 
                                  background: task.status === 'completed' ? 'var(--status-available)' :
                                              task.status === 'in_progress' ? 'var(--primary)' :
                                              task.status === 'cancelled' ? '#9ca3af' : '#d97706' 
                                }}>
                                  {task.status === 'in_progress' ? 'In Progress' : task.status}
                                </span>
                              </td>
                              <td>
                                {task.status !== 'completed' && task.status !== 'cancelled' ? (
                                  <select 
                                    value={task.assigned_to || ''} 
                                    onChange={(e) => handleAssignTask(task.id, e.target.value)}
                                    className="select-dropdown"
                                    style={{ padding: '4px', fontSize: '12px', background: 'rgba(255,255,255,0.1)', color: '#fff' }}
                                  >
                                    <option value="" style={{ color: '#000' }}>Unassigned</option>
                                    {users.map(u => (
                                      <option key={u.id} value={u.id} style={{ color: '#000' }}>{u.username}</option>
                                    ))}
                                  </select>
                                ) : (
                                  <span>{task.assignee_name || 'N/A'}</span>
                                )}
                              </td>
                              <td style={{ fontSize: '12px', maxWidth: '150px', overflow: 'hidden', textOverflow: 'ellipsis' }}>{task.notes || '-'}</td>
                              <td>
                                <div style={{ display: 'flex', gap: '5px' }}>
                                  {task.status === 'pending' && (
                                    <button onClick={() => handleUpdateHkStatus(task.id, 'in_progress')} className="btn btn-sm btn-secondary" style={{ padding: '3px 6px', fontSize: '11px' }}>Start</button>
                                  )}
                                  {task.status === 'in_progress' && (
                                    <button onClick={() => handleUpdateHkStatus(task.id, 'completed')} className="btn btn-sm" style={{ padding: '3px 6px', fontSize: '11px', background: 'var(--status-available)', color: '#fff' }}>Complete</button>
                                  )}
                                  {task.status !== 'completed' && task.status !== 'cancelled' && (
                                    <button onClick={() => handleUpdateHkStatus(task.id, 'cancelled')} className="btn btn-sm btn-secondary" style={{ padding: '3px 6px', fontSize: '11px', color: 'var(--status-occupied)' }}>Cancel</button>
                                  )}
                                </div>
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>

                {/* Form to Log New Housekeeping Task */}
                <div className="glass-panel">
                  <h3 className="page-title" style={{ fontSize: '18px', marginBottom: '15px' }}>Report Issue / Create Task</h3>
                  <form onSubmit={handleCreateHkTask}>
                    <div className="form-group" style={{ marginBottom: '12px' }}>
                      <label className="form-label">Linked Room</label>
                      <select className="select-dropdown" value={newHkRoomId} onChange={(e) => setNewHkRoomId(e.target.value)}>
                        <option value="">General Facility (None)</option>
                        {rooms.map(r => (
                          <option key={r.id} value={r.id}>Room {r.room_number} ({r.status})</option>
                        ))}
                      </select>
                    </div>

                    <div className="form-group" style={{ marginBottom: '12px' }}>
                      <label className="form-label">Task Type</label>
                      <select className="select-dropdown" value={newHkType} onChange={(e) => setNewHkType(e.target.value)}>
                        <option value="cleaning">Room Cleaning</option>
                        <option value="maintenance">Maintenance Repair</option>
                        <option value="laundry">Laundry Service</option>
                      </select>
                    </div>

                    <div className="form-group" style={{ marginBottom: '12px' }}>
                      <label className="form-label">Priority</label>
                      <select className="select-dropdown" value={newHkPriority} onChange={(e) => setNewHkPriority(e.target.value)}>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High (Urgent)</option>
                      </select>
                    </div>

                    <div className="form-group" style={{ marginBottom: '12px' }}>
                      <label className="form-label">Assign Worker</label>
                      <select className="select-dropdown" value={newHkAssignee} onChange={(e) => setNewHkAssignee(e.target.value)}>
                        <option value="">Unassigned</option>
                        {users.map(u => (
                          <option key={u.id} value={u.id}> {u.username} </option>
                        ))}
                      </select>
                    </div>

                    <div className="form-group" style={{ marginBottom: '15px' }}>
                      <label className="form-label">Task Details / Operator Notes</label>
                      <textarea 
                        className="form-input" 
                        rows={3} 
                        placeholder="Describe cleaning instructions or maintenance request..." 
                        value={newHkNotes}
                        onChange={(e) => setNewHkNotes(e.target.value)}
                        required
                      />
                    </div>

                    <button type="submit" className="btn" style={{ width: '100%' }}>Create Task</button>
                  </form>
                </div>

              </div>
            </div>
          )}

          {/* 13. INVENTORY & PURCHASES DASHBOARD */}
          {activeTab === 'inventory' && (
            <div>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h2 className="page-title" style={{ margin: 0 }}>Inventory, Stock Costing, & POs</h2>
                
                {/* Nested Sub-tab controls */}
                <div style={{ display: 'flex', gap: '8px' }}>
                  <button onClick={() => setInvSubTab('stock')} className={`btn ${invSubTab === 'stock' ? '' : 'btn-secondary'}`} style={{ fontSize: '13px', padding: '6px 12px' }}>Stock Ledger</button>
                  <button onClick={() => setInvSubTab('vendors')} className={`btn ${invSubTab === 'vendors' ? '' : 'btn-secondary'}`} style={{ fontSize: '13px', padding: '6px 12px' }}>Vendors</button>
                  <button onClick={() => setInvSubTab('po')} className={`btn ${invSubTab === 'po' ? '' : 'btn-secondary'}`} style={{ fontSize: '13px', padding: '6px 12px' }}>Purchase Orders</button>
                  <button onClick={() => setInvSubTab('grn')} className={`btn ${invSubTab === 'grn' ? '' : 'btn-secondary'}`} style={{ fontSize: '13px', padding: '6px 12px' }}>Goods Receipts (GRN)</button>
                </div>
              </div>

              {/* SECTION A: STOCK INVENTORY */}
              {invSubTab === 'stock' && (
                <div className="glass-panel">
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' }}>
                    <h3 style={{ margin: 0, fontSize: '18px' }}>Valuation Directory (Weighted Average Costing)</h3>
                    <button onClick={() => setShowCreateItemModal(true)} className="btn btn-sm">Add Stock Item</button>
                  </div>
                  <div className="table-wrapper">
                    <table className="table">
                      <thead>
                        <tr>
                          <th>SKU / Code</th>
                          <th>Item Name</th>
                          <th>Category</th>
                          <th>UOM</th>
                          <th>Min Level</th>
                          <th>Current Stock</th>
                          <th>Weighted Avg Cost</th>
                          <th>Valuation</th>
                          <th>Alerts</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {inventoryItems.length === 0 ? (
                          <tr>
                            <td colSpan={10} style={{ textAlign: 'center', color: '#9ca3af', fontStyle: 'italic' }}>No inventory items registered.</td>
                          </tr>
                        ) : (
                          inventoryItems.map(item => {
                            const isLow = parseFloat(item.current_stock) < parseFloat(item.min_stock_level);
                            const totalValuation = parseFloat(item.current_stock) * parseFloat(item.average_unit_cost);
                            return (
                              <tr key={item.id}>
                                <td><code>{item.sku}</code></td>
                                <td><strong>{item.name}</strong></td>
                                <td>{item.category || '-'}</td>
                                <td>{item.unit_of_measure}</td>
                                <td>{parseFloat(item.min_stock_level).toFixed(2)}</td>
                                <td><strong>{parseFloat(item.current_stock).toFixed(2)}</strong></td>
                                <td>{parseFloat(item.average_unit_cost).toFixed(2)} INR</td>
                                <td><strong>{totalValuation.toFixed(2)} INR</strong></td>
                                <td>
                                  {isLow ? (
                                    <span style={{ padding: '2px 8px', borderRadius: '12px', fontSize: '11px', background: 'var(--status-occupied)', color: '#fff', fontWeight: 'bold' }}>Low Stock</span>
                                  ) : (
                                    <span style={{ padding: '2px 8px', borderRadius: '12px', fontSize: '11px', background: 'var(--status-available)', color: '#fff', fontWeight: 'bold' }}>Healthy</span>
                                  )}
                                </td>
                                <td>
                                  <div style={{ display: 'flex', gap: '5px' }}>
                                    <button onClick={() => {
                                      setSelectedHkItem(item);
                                      setAdjustQty('');
                                      setAdjustCost(parseFloat(item.average_unit_cost) > 0 ? String(item.average_unit_cost) : '');
                                      setAdjustReason('');
                                      setShowAdjustModal(true);
                                    }} className="btn btn-secondary btn-sm" style={{ padding: '4px 8px', fontSize: '11px' }}>Adjust</button>
                                    <button onClick={() => handleFetchItemLedger(item)} className="btn btn-secondary btn-sm" style={{ padding: '4px 8px', fontSize: '11px' }}>Audit Logs</button>
                                  </div>
                                </td>
                              </tr>
                            );
                          })
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {/* SECTION B: VENDORS REGISTRY */}
              {invSubTab === 'vendors' && (
                <div className="glass-panel">
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' }}>
                    <h3 style={{ margin: 0, fontSize: '18px' }}>Active Vendors Log</h3>
                    <button onClick={() => setShowCreateVendorModal(true)} className="btn btn-sm">Add Vendor</button>
                  </div>
                  <div className="table-wrapper">
                    <table className="table">
                      <thead>
                        <tr>
                          <th>Vendor Name</th>
                          <th>Contact Name</th>
                          <th>Phone</th>
                          <th>Email</th>
                          <th>Address</th>
                          <th>GSTIN</th>
                        </tr>
                      </thead>
                      <tbody>
                        {vendors.length === 0 ? (
                          <tr>
                            <td colSpan={6} style={{ textAlign: 'center', color: '#9ca3af', fontStyle: 'italic' }}>No vendors registered.</td>
                          </tr>
                        ) : (
                          vendors.map(v => (
                            <tr key={v.id}>
                              <td><strong>{v.name}</strong></td>
                              <td>{v.contact_name || '-'}</td>
                              <td>{v.phone || '-'}</td>
                              <td>{v.email || '-'}</td>
                              <td style={{ fontSize: '12px' }}>{v.address || '-'}</td>
                              <td><code>{v.gst_number || '-'}</code></td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {/* SECTION C: PURCHASE ORDERS */}
              {invSubTab === 'po' && (
                <div className="glass-panel">
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' }}>
                    <h3 style={{ margin: 0, fontSize: '18px' }}>Purchase Order Planner & Approvals</h3>
                    <button onClick={() => setShowCreatePoModal(true)} className="btn btn-sm">Create PO</button>
                  </div>
                  <div className="table-wrapper">
                    <table className="table">
                      <thead>
                        <tr>
                          <th>PO Number</th>
                          <th>Vendor</th>
                          <th>Total Amount</th>
                          <th>Status</th>
                          <th>Created By</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {purchaseOrders.length === 0 ? (
                          <tr>
                            <td colSpan={6} style={{ textAlign: 'center', color: '#9ca3af', fontStyle: 'italic' }}>No purchase orders found.</td>
                          </tr>
                        ) : (
                          purchaseOrders.map(po => (
                            <tr key={po.id}>
                              <td><code>{po.po_number}</code></td>
                              <td><strong>{po.vendor_name}</strong></td>
                              <td>{parseFloat(po.total_amount).toFixed(2)} INR</td>
                              <td>
                                <span className="room-status-badge" style={{ 
                                  margin: 0, 
                                  background: po.status === 'Approved' ? 'var(--status-available)' :
                                              po.status === 'Received' ? 'var(--primary)' :
                                              po.status === 'Rejected' ? 'var(--status-occupied)' : '#d97706' 
                                }}>
                                  {po.status}
                                </span>
                              </td>
                              <td>{po.creator_name}</td>
                              <td>
                                <div style={{ display: 'flex', gap: '5px' }}>
                                  {po.status === 'Pending Approval' && (
                                    <>
                                      <button onClick={() => handleApprovePo(po.id, 'Approved')} className="btn btn-sm" style={{ padding: '4px 8px', fontSize: '11px', background: 'var(--status-available)', color: '#fff' }}>Approve</button>
                                      <button onClick={() => handleApprovePo(po.id, 'Rejected')} className="btn btn-secondary btn-sm" style={{ padding: '4px 8px', fontSize: '11px', color: 'var(--status-occupied)' }}>Reject</button>
                                    </>
                                  )}
                                  {po.status === 'Approved' && (
                                    <button onClick={() => handleOpenGrnModal(po)} className="btn btn-sm" style={{ padding: '4px 8px', fontSize: '11px', background: 'var(--primary)', color: '#fff' }}>Receive Items (GRN)</button>
                                  )}
                                </div>
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {/* SECTION D: GOODS RECEIPTS (GRN) */}
              {invSubTab === 'grn' && (
                <div className="glass-panel">
                  <h3 style={{ marginBottom: '15px', fontSize: '18px' }}>Goods Receipt Notes (GRN) History</h3>
                  <div className="table-wrapper">
                    <table className="table">
                      <thead>
                        <tr>
                          <th>GRN Number</th>
                          <th>PO Link</th>
                          <th>Received Date</th>
                          <th>Received By</th>
                          <th>Notes</th>
                        </tr>
                      </thead>
                      <tbody>
                        {goodsReceipts.length === 0 ? (
                          <tr>
                            <td colSpan={5} style={{ textAlign: 'center', color: '#9ca3af', fontStyle: 'italic' }}>No goods receipt notes logged.</td>
                          </tr>
                        ) : (
                          goodsReceipts.map(grn => (
                            <tr key={grn.id}>
                              <td><code>{grn.grn_number}</code></td>
                              <td><code>{grn.po_number || 'Direct'}</code></td>
                              <td>{new Date(grn.received_date).toLocaleDateString()}</td>
                              <td>{grn.creator_name}</td>
                              <td style={{ fontSize: '12px' }}>{grn.notes || '-'}</td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* 14. KITCHEN ROOM SERVICE & RECIPES DASHBOARD */}
          {activeTab === 'kitchen' && (
            <div>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h2 className="page-title" style={{ margin: 0 }}>Kitchen Room Service & Costing</h2>
                
                {/* Sub tabs */}
                <div style={{ display: 'flex', gap: '8px' }}>
                  <button onClick={() => setKitchenSubTab('orders')} className={`btn ${kitchenSubTab === 'orders' ? '' : 'btn-secondary'}`} style={{ fontSize: '13px', padding: '6px 12px' }}>Room Service Orders</button>
                  <button onClick={() => setKitchenSubTab('menu')} className={`btn ${kitchenSubTab === 'menu' ? '' : 'btn-secondary'}`} style={{ fontSize: '13px', padding: '6px 12px' }}>Menu & Recipes</button>
                  <button onClick={() => setKitchenSubTab('costing')} className={`btn ${kitchenSubTab === 'costing' ? '' : 'btn-secondary'}`} style={{ fontSize: '13px', padding: '6px 12px' }}>Costing Sheets</button>
                </div>
              </div>

              {/* ORDERS TRACKER */}
              {kitchenSubTab === 'orders' && (
                <div className="glass-panel">
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' }}>
                    <h3 style={{ margin: 0, fontSize: '18px' }}>Active Room Service Orders</h3>
                    <button onClick={() => setShowCreateKitchenOrderModal(true)} className="btn btn-sm">Place Kitchen Order</button>
                  </div>
                  <div className="table-wrapper">
                    <table className="table">
                      <thead>
                        <tr>
                          <th>Order #</th>
                          <th>Room</th>
                          <th>Stay ID</th>
                          <th>Included Meal Plan</th>
                          <th>Total Amount</th>
                          <th>Status</th>
                          <th>Notes</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {kitchenOrders.length === 0 ? (
                          <tr>
                            <td colSpan={8} style={{ textAlign: 'center', color: '#9ca3af', fontStyle: 'italic' }}>No room service orders found.</td>
                          </tr>
                        ) : (
                          kitchenOrders.map(o => (
                            <tr key={o.id}>
                              <td><code>{o.order_number}</code></td>
                              <td><strong>{o.room_number}</strong></td>
                              <td>#{o.stay_id}</td>
                              <td>{o.is_meal_plan_included ? 'Yes (Included)' : 'No (Chargeable)'}</td>
                              <td>{parseFloat(o.total_amount).toFixed(2)} INR</td>
                              <td>
                                <span className="room-status-badge" style={{ 
                                  margin: 0, 
                                  background: o.status === 'served' ? 'var(--status-available)' :
                                              o.status === 'preparing' ? '#d97706' :
                                              o.status === 'cancelled' ? 'var(--status-occupied)' : 'var(--primary)' 
                                }}>
                                  {o.status}
                                </span>
                              </td>
                              <td style={{ fontSize: '12px' }}>{o.notes || '-'}</td>
                              <td>
                                <div style={{ display: 'flex', gap: '5px' }}>
                                  {o.status === 'pending' && (
                                    <button onClick={() => handleUpdateKitchenOrderStatus(o.id, 'preparing')} className="btn btn-sm" style={{ padding: '4px 8px', fontSize: '11px', background: '#d97706', color: '#fff' }}>Prepare</button>
                                  )}
                                  {o.status === 'preparing' && (
                                    <button onClick={() => handleUpdateKitchenOrderStatus(o.id, 'served')} className="btn btn-sm" style={{ padding: '4px 8px', fontSize: '11px', background: 'var(--status-available)', color: '#fff' }}>Serve (Complete)</button>
                                  )}
                                  {(o.status === 'pending' || o.status === 'preparing') && (
                                    <button onClick={() => handleUpdateKitchenOrderStatus(o.id, 'cancelled')} className="btn btn-secondary btn-sm" style={{ padding: '4px 8px', fontSize: '11px', color: 'var(--status-occupied)' }}>Cancel</button>
                                  )}
                                </div>
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {/* MENU & RECIPES */}
              {kitchenSubTab === 'menu' && (
                <div className="glass-panel">
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' }}>
                    <h3 style={{ margin: 0, fontSize: '18px' }}>Kitchen Menu Items</h3>
                    <button onClick={() => setShowCreateMenuItemModal(true)} className="btn btn-sm">Add Menu Item</button>
                  </div>
                  <div className="table-wrapper">
                    <table className="table">
                      <thead>
                        <tr>
                          <th>Item Name</th>
                          <th>Description</th>
                          <th>Price</th>
                          <th>Status</th>
                          <th>Recipe Builder</th>
                        </tr>
                      </thead>
                      <tbody>
                        {kitchenItems.length === 0 ? (
                          <tr>
                            <td colSpan={5} style={{ textAlign: 'center', color: '#9ca3af', fontStyle: 'italic' }}>No menu items found.</td>
                          </tr>
                        ) : (
                          kitchenItems.map(item => (
                            <tr key={item.id}>
                              <td><strong>{item.name}</strong></td>
                              <td style={{ fontSize: '12px' }}>{item.description || '-'}</td>
                              <td>{parseFloat(item.price).toFixed(2)} INR</td>
                              <td>
                                <span style={{ 
                                  padding: '2px 8px', 
                                  borderRadius: '12px', 
                                  fontSize: '11px', 
                                  background: item.is_active ? 'var(--status-available)' : 'var(--status-occupied)', 
                                  color: '#fff', 
                                  fontWeight: 'bold' 
                                }}>
                                  {item.is_active ? 'Active' : 'Inactive'}
                                </span>
                              </td>
                              <td>
                                <button onClick={async () => {
                                  // Fetch recipe details
                                  try {
                                    const r = await fetch(`/api/hotels/${activeHotelId}/kitchen/menu-items/${item.id}/recipe`, { headers: getHeaders() });
                                    const d = await r.json();
                                    setSelectedKItem(item);
                                    if (d.success && d.data) {
                                      setRecipeInstructions(d.data.instructions || '');
                                      setRecipeIngredients(d.data.items.map((ri: any) => ({
                                        inventory_item_id: ri.inventory_item_id,
                                        quantity: ri.quantity
                                      })));
                                    } else {
                                      setRecipeInstructions('');
                                      setRecipeIngredients([{ inventory_item_id: '', quantity: '' }]);
                                    }
                                    setShowRecipeModal(true);
                                  } catch (e) {
                                    showFeedback('danger', 'Failed to load recipe.');
                                  }
                                }} className="btn btn-secondary btn-sm" style={{ padding: '4px 8px', fontSize: '11px' }}>Configure Recipe</button>
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {/* COSTING SHEETS */}
              {kitchenSubTab === 'costing' && (
                <div className="glass-panel">
                  <h3 style={{ marginBottom: '15px', fontSize: '18px' }}>Recipe Costing Sheets & GP Margins</h3>
                  <div className="table-wrapper">
                    <table className="table">
                      <thead>
                        <tr>
                          <th>Menu Item</th>
                          <th>Price</th>
                          <th>Ingredient Cost (Avg Unit Costs)</th>
                          <th>Gross Profit</th>
                          <th>Profit Margin %</th>
                          <th>Has Recipe</th>
                        </tr>
                      </thead>
                      <tbody>
                        {kitchenCostingSheet.length === 0 ? (
                          <tr>
                            <td colSpan={6} style={{ textAlign: 'center', color: '#9ca3af', fontStyle: 'italic' }}>No costing records compiled.</td>
                          </tr>
                        ) : (
                          kitchenCostingSheet.map(row => (
                            <tr key={row.item_id}>
                              <td><strong>{row.name}</strong></td>
                              <td>{parseFloat(row.price).toFixed(2)} INR</td>
                              <td>{parseFloat(row.recipe_cost).toFixed(2)} INR</td>
                              <td style={{ color: row.gross_profit >= 0 ? 'var(--status-available)' : 'var(--status-occupied)', fontWeight: 'bold' }}>
                                {parseFloat(row.gross_profit).toFixed(2)} INR
                              </td>
                              <td>
                                <strong style={{ color: row.margin_percentage >= 35 ? 'var(--status-available)' : '#cbd5e1' }}>
                                  {parseFloat(row.margin_percentage).toFixed(1)}%
                                </strong>
                              </td>
                              <td>
                                {row.has_recipe ? (
                                  <span style={{ padding: '2px 8px', borderRadius: '12px', fontSize: '11px', background: 'var(--status-available)', color: '#fff', fontWeight: 'bold' }}>Configured</span>
                                ) : (
                                  <span style={{ padding: '2px 8px', borderRadius: '12px', fontSize: '11px', background: '#d97706', color: '#fff', fontWeight: 'bold' }}>Missing Recipe</span>
                                )}
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* 15. EMPLOYEES & ATTENDANCE DASHBOARD */}
          {activeTab === 'employees' && (
            <div>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
                <h2 className="page-title" style={{ margin: 0 }}>Staff Registry & Attendance</h2>
                
                {/* Sub tabs */}
                <div style={{ display: 'flex', gap: '8px' }}>
                  <button onClick={() => setEmpSubTab('roster')} className={`btn ${empSubTab === 'roster' ? '' : 'btn-secondary'}`} style={{ fontSize: '13px', padding: '6px 12px' }}>Staff Roster</button>
                  <button onClick={() => setEmpSubTab('attendance')} className={`btn ${empSubTab === 'attendance' ? '' : 'btn-secondary'}`} style={{ fontSize: '13px', padding: '6px 12px' }}>Attendance Board</button>
                  <button onClick={() => setEmpSubTab('settings')} className={`btn ${empSubTab === 'settings' ? '' : 'btn-secondary'}`} style={{ fontSize: '13px', padding: '6px 12px' }}>Shifts & Departments</button>
                </div>
              </div>

              {/* STAFF ROSTER PANEL */}
              {empSubTab === 'roster' && (
                <div className="glass-panel">
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' }}>
                    <h3 style={{ margin: 0, fontSize: '18px' }}>Active Hotel Staff</h3>
                    <button onClick={() => setShowCreateEmployeeModal(true)} className="btn btn-sm">Add Employee Profile</button>
                  </div>
                  <div className="table-wrapper">
                    <table className="table">
                      <thead>
                        <tr>
                          <th>Code</th>
                          <th>Full Name</th>
                          <th>Department</th>
                          <th>Shift</th>
                          <th>Contact Details</th>
                          <th>Emergency Contact</th>
                          <th>Base Salary</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        {employees.length === 0 ? (
                          <tr>
                            <td colSpan={8} style={{ textAlign: 'center', color: '#9ca3af', fontStyle: 'italic' }}>No employee profiles logged.</td>
                          </tr>
                        ) : (
                          employees.map(emp => (
                            <tr key={emp.id}>
                              <td><code>{emp.employee_code}</code></td>
                              <td><strong>{emp.first_name} {emp.last_name}</strong></td>
                              <td>{emp.department_name || <span style={{ color: '#64748b', fontStyle: 'italic' }}>General</span>}</td>
                              <td>
                                {emp.shift_name ? (
                                  <span>{emp.shift_name} <span style={{ fontSize: '11px', color: '#94a3b8' }}>({emp.start_time.substring(0,5)}-{emp.end_time.substring(0,5)})</span></span>
                                ) : (
                                  <span style={{ color: '#64748b', fontStyle: 'italic' }}>General</span>
                                )}
                              </td>
                              <td style={{ fontSize: '12px' }}>
                                <div>📞 {emp.phone || '-'}</div>
                                <div>✉️ {emp.email || '-'}</div>
                              </td>
                              <td style={{ fontSize: '12px' }}>
                                {emp.emergency_contact_name ? (
                                  <div>
                                    <strong>{emp.emergency_contact_name}</strong>
                                    <div>📞 {emp.emergency_contact_phone || '-'}</div>
                                  </div>
                                ) : (
                                  '-'
                                )}
                              </td>
                              <td>
                                {emp.salary_base !== undefined ? (
                                  <strong>{parseFloat(emp.salary_base).toFixed(2)} INR</strong>
                                ) : (
                                  <span style={{ color: '#94a3b8', fontStyle: 'italic', fontSize: '12px' }}>[RESTRICTED]</span>
                                )}
                              </td>
                              <td>
                                <span style={{ 
                                  padding: '2px 8px', 
                                  borderRadius: '12px', 
                                  fontSize: '11px', 
                                  background: emp.status === 'Active' ? 'var(--status-available)' : 'var(--status-occupied)', 
                                  color: '#fff', 
                                  fontWeight: 'bold' 
                                }}>
                                  {emp.status}
                                </span>
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {/* ATTENDANCE BOARD PANEL */}
              {empSubTab === 'attendance' && (
                <div className="glass-panel">
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px', flexWrap: 'wrap', gap: '10px' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                      <h3 style={{ margin: 0, fontSize: '18px' }}>Daily Attendance</h3>
                      <input
                        type="date"
                        className="form-input"
                        style={{ width: '150px', padding: '4px 8px', margin: 0 }}
                        value={attendanceDate}
                        onChange={(e) => setAttendanceDate(e.target.value)}
                      />
                    </div>
                    <button onClick={async () => {
                      // Save bulk attendance roster
                      const roster = employees.filter(e => e.status === 'Active').map(e => {
                        const existing = attendanceList.find(a => a.employee_id === e.id);
                        return {
                          employee_id: e.id,
                          status: existing ? existing.status : 'Present',
                          notes: existing ? existing.notes : ''
                        };
                      });
                      
                      try {
                        const res = await fetch(`/api/hotels/${activeHotelId}/employees/attendance/bulk`, {
                          method: 'POST',
                          headers: getHeaders(),
                          body: JSON.stringify({
                            work_date: attendanceDate,
                            roster
                          })
                        });
                        const data = await res.json();
                        if (data.success) {
                          showFeedback('success', 'Roster attendance logs saved successfully.');
                          fetchScopedData();
                        } else {
                          showFeedback('danger', data.message);
                        }
                      } catch (e) {
                        showFeedback('danger', 'Failed to save attendance.');
                      }
                    }} className="btn btn-sm">Save Day Attendance Statuses</button>
                  </div>
                  
                  <div className="table-wrapper">
                    <table className="table">
                      <thead>
                        <tr>
                          <th>Code</th>
                          <th>Employee</th>
                          <th>Department</th>
                          <th>Shift</th>
                          <th>Clock In</th>
                          <th>Clock Out</th>
                          <th>Status Tag</th>
                          <th>Remarks</th>
                          <th>Timeclock Quick Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        {employees.filter(e => e.status === 'Active').length === 0 ? (
                          <tr>
                            <td colSpan={9} style={{ textAlign: 'center', color: '#9ca3af', fontStyle: 'italic' }}>No active employees found in roster.</td>
                          </tr>
                        ) : (
                          employees.filter(e => e.status === 'Active').map(emp => {
                            const att = attendanceList.find(a => a.employee_id === emp.id);
                            
                            return (
                              <tr key={emp.id}>
                                <td><code>{emp.employee_code}</code></td>
                                <td><strong>{emp.first_name} {emp.last_name}</strong></td>
                                <td>{emp.department_name || 'General'}</td>
                                <td>{emp.shift_name || 'General'}</td>
                                <td>{att && att.clock_in ? new Date(att.clock_in).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '-'}</td>
                                <td>{att && att.clock_out ? new Date(att.clock_out).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '-'}</td>
                                <td>
                                  <select 
                                    className="select-dropdown" 
                                    style={{ padding: '2px 6px', fontSize: '12px', margin: 0, width: '110px' }}
                                    value={att ? att.status : 'Present'}
                                    onChange={(e) => {
                                      const updatedList = [...attendanceList];
                                      const idx = updatedList.findIndex(a => a.employee_id === emp.id);
                                      if (idx >= 0) {
                                        updatedList[idx].status = e.target.value;
                                      } else {
                                        updatedList.push({
                                          employee_id: emp.id,
                                          status: e.target.value,
                                          notes: ''
                                        });
                                      }
                                      setAttendanceList(updatedList);
                                    }}
                                  >
                                    <option value="Present">Present</option>
                                    <option value="Absent">Absent</option>
                                    <option value="Leave">Leave</option>
                                    <option value="Late">Late</option>
                                  </select>
                                </td>
                                <td>
                                  <input
                                    type="text"
                                    placeholder="Remarks..."
                                    className="form-input"
                                    style={{ margin: 0, padding: '2px 6px', fontSize: '12px', minWidth: '100px' }}
                                    value={att && att.notes ? att.notes : ''}
                                    onChange={(e) => {
                                      const updatedList = [...attendanceList];
                                      const idx = updatedList.findIndex(a => a.employee_id === emp.id);
                                      if (idx >= 0) {
                                        updatedList[idx].notes = e.target.value;
                                      } else {
                                        updatedList.push({
                                          employee_id: emp.id,
                                          status: 'Present',
                                          notes: e.target.value
                                        });
                                      }
                                      setAttendanceList(updatedList);
                                    }}
                                  />
                                </td>
                                <td>
                                  <div style={{ display: 'flex', gap: '5px' }}>
                                    {(!att || !att.clock_in) ? (
                                      <button 
                                        onClick={() => handleClockEmployee(emp.id, 'in')} 
                                        className="btn btn-sm" 
                                        style={{ padding: '3px 8px', fontSize: '11px', background: 'var(--status-available)', color: '#fff' }}
                                      >
                                        Clock In
                                      </button>
                                    ) : (!att.clock_out) ? (
                                      <button 
                                        onClick={() => handleClockEmployee(emp.id, 'out')} 
                                        className="btn btn-secondary btn-sm" 
                                        style={{ padding: '3px 8px', fontSize: '11px', color: 'var(--status-occupied)' }}
                                      >
                                        Clock Out
                                      </button>
                                    ) : (
                                      <span style={{ fontSize: '11px', color: '#16a34a', fontWeight: 'bold' }}>✓ Shift Complete</span>
                                    )}
                                  </div>
                                </td>
                              </tr>
                            );
                          })
                        )}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {/* SETTINGS (SHIFTS & DEPARTMENTS) */}
              {empSubTab === 'settings' && (
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px' }}>
                  {/* DEPARTMENTS CARD */}
                  <div className="glass-panel">
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' }}>
                      <h3 style={{ margin: 0, fontSize: '17px' }}>Departments</h3>
                      <button onClick={() => setShowCreateDeptModal(true)} className="btn btn-secondary btn-sm">+ Add Dept</button>
                    </div>
                    <div className="table-wrapper">
                      <table className="table">
                        <thead>
                          <tr>
                            <th>Code</th>
                            <th>Name</th>
                          </tr>
                        </thead>
                        <tbody>
                          {departments.length === 0 ? (
                            <tr>
                              <td colSpan={2} style={{ textAlign: 'center', color: '#9ca3af', fontStyle: 'italic' }}>No departments logged.</td>
                            </tr>
                          ) : (
                            departments.map(dept => (
                              <tr key={dept.id}>
                                <td><code>{dept.code}</code></td>
                                <td><strong>{dept.name}</strong></td>
                              </tr>
                            ))
                          )}
                        </tbody>
                      </table>
                    </div>
                  </div>

                  {/* SHIFTS CARD */}
                  <div className="glass-panel">
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' }}>
                      <h3 style={{ margin: 0, fontSize: '17px' }}>Shifts Calendar</h3>
                      <button onClick={() => setShowCreateShiftModal(true)} className="btn btn-secondary btn-sm">+ Add Shift</button>
                    </div>
                    <div className="table-wrapper">
                      <table className="table">
                        <thead>
                          <tr>
                            <th>Name</th>
                            <th>Hours</th>
                          </tr>
                        </thead>
                        <tbody>
                          {shifts.length === 0 ? (
                            <tr>
                              <td colSpan={2} style={{ textAlign: 'center', color: '#9ca3af', fontStyle: 'italic' }}>No shift schedules logged.</td>
                            </tr>
                          ) : (
                            shifts.map(sh => (
                              <tr key={sh.id}>
                                <td><strong>{sh.name}</strong></td>
                                <td><code>{sh.start_time.substring(0, 5)} - {sh.end_time.substring(0, 5)}</code></td>
                              </tr>
                            ))
                          )}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              )}
            </div>
          )}
        </div>
      </main>

      {/* MODAL 4: INVOICE PRINT PREVIEW */}
      {selectedInvoice && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card" style={{ maxWidth: '800px', width: '90%', background: '#fff', color: '#1e293b' }}>
            <div className="modal-header" style={{ borderBottom: '2px solid #e2e8f0', paddingBottom: '10px' }}>
              <h3 className="modal-title" style={{ color: '#1e293b' }}>TAX INVOICE - {selectedInvoice.invoice_number}</h3>
              <button onClick={() => setSelectedInvoice(null)} className="modal-close" style={{ color: '#1e293b' }}>×</button>
            </div>
            
            <div style={{ padding: '20px 0', fontSize: '14px', lineHeight: '1.5' }}>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px', marginBottom: '25px', borderBottom: '1px solid #e2e8f0', paddingBottom: '20px' }}>
                <div>
                  <h4 style={{ margin: '0 0 8px 0', fontSize: '16px', color: '#0f172a' }}>HMS Core Tenancy Hotel</h4>
                  <p style={{ margin: '0' }}><strong>GSTIN:</strong> 07AAAAA1111A1Z1</p>
                  <p style={{ margin: '0' }}>New Delhi, Delhi NCR, India</p>
                </div>
                <div style={{ textAlign: 'right' }}>
                  <p style={{ margin: '0' }}><strong>Invoice Number:</strong> {selectedInvoice.invoice_number}</p>
                  <p style={{ margin: '0' }}><strong>Invoice Date:</strong> {new Date(selectedInvoice.created_at).toLocaleDateString()}</p>
                  <p style={{ margin: '0' }}><strong>Status:</strong> <span style={{ color: '#16a34a', fontWeight: 'bold' }}>{selectedInvoice.status}</span></p>
                </div>
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px', marginBottom: '25px' }}>
                <div>
                  <h5 style={{ margin: '0 0 6px 0', color: '#64748b', fontSize: '12px', textTransform: 'uppercase' }}>Guest Details</h5>
                  <p style={{ margin: '0' }}><strong>Name:</strong> {selectedInvoice.first_name} {selectedInvoice.last_name}</p>
                  <p style={{ margin: '0' }}><strong>Phone:</strong> {selectedInvoice.phone || 'N/A'}</p>
                  <p style={{ margin: '0' }}><strong>Email:</strong> {selectedInvoice.email || 'N/A'}</p>
                </div>
                {selectedInvoice.stay_details && (
                  <div style={{ textAlign: 'right' }}>
                    <h5 style={{ margin: '0 0 6px 0', color: '#64748b', fontSize: '12px', textTransform: 'uppercase' }}>Stay Details</h5>
                    <p style={{ margin: '0' }}><strong>Stay ID:</strong> #{selectedInvoice.stay_details.id}</p>
                    <p style={{ margin: '0' }}><strong>Check-in:</strong> {new Date(selectedInvoice.stay_details.checkin_at).toLocaleString()}</p>
                    <p style={{ margin: '0' }}><strong>Checkout:</strong> {new Date(selectedInvoice.stay_details.checked_out_at).toLocaleString()}</p>
                  </div>
                )}
              </div>

              <table style={{ width: '100%', borderCollapse: 'collapse', marginBottom: '25px' }}>
                <thead>
                  <tr style={{ background: '#f8fafc', borderBottom: '2px solid #cbd5e1' }}>
                    <th style={{ textAlign: 'left', padding: '10px' }}>Description</th>
                    <th style={{ textAlign: 'right', padding: '10px' }}>Base Price</th>
                    <th style={{ textAlign: 'right', padding: '10px' }}>Taxable Amt</th>
                    <th style={{ textAlign: 'right', padding: '10px' }}>Tax Amt</th>
                    <th style={{ textAlign: 'right', padding: '10px' }}>Total Price</th>
                  </tr>
                </thead>
                <tbody>
                  {selectedInvoice.stay_details && selectedInvoice.stay_details.folio.filter((item: any) => item.item_type !== 'payment_credit').map((item: any) => {
                    const baseAmt = parseFloat(item.amount);
                    const taxAmt = parseFloat(item.tax_amount || '0.00');
                    return (
                      <tr key={item.id} style={{ borderBottom: '1px solid #e2e8f0' }}>
                        <td style={{ padding: '10px' }}>{item.description}</td>
                        <td style={{ padding: '10px', textAlign: 'right' }}>{baseAmt.toFixed(2)}</td>
                        <td style={{ padding: '10px', textAlign: 'right' }}>{baseAmt.toFixed(2)}</td>
                        <td style={{ padding: '10px', textAlign: 'right' }}>{taxAmt.toFixed(2)}</td>
                        <td style={{ padding: '10px', textAlign: 'right' }}>{(baseAmt + taxAmt).toFixed(2)}</td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>

              <div style={{ display: 'grid', gridTemplateColumns: '1.5fr 1fr', gap: '20px' }}>
                <div></div>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '8px', background: '#f8fafc', padding: '15px', borderRadius: '6px' }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                    <span>Subtotal:</span>
                    <span>{parseFloat(selectedInvoice.subtotal).toFixed(2)} INR</span>
                  </div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '13px', color: '#64748b' }}>
                    <span>CGST:</span>
                    <span>{parseFloat(selectedInvoice.cgst).toFixed(2)}</span>
                  </div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '13px', color: '#64748b' }}>
                    <span>SGST:</span>
                    <span>{parseFloat(selectedInvoice.sgst).toFixed(2)}</span>
                  </div>
                  {parseFloat(selectedInvoice.discount) > 0 && (
                    <div style={{ display: 'flex', justifyContent: 'space-between', color: '#ef4444' }}>
                      <span>Discount Adjustments:</span>
                      <span>-{parseFloat(selectedInvoice.discount).toFixed(2)}</span>
                    </div>
                  )}
                  <div style={{ display: 'flex', justifyContent: 'space-between', borderTop: '2px solid #cbd5e1', paddingTop: '8px', fontWeight: 'bold', fontSize: '16px', color: '#0f172a' }}>
                    <span>Grand Total:</span>
                    <span>{parseFloat(selectedInvoice.total_amount).toFixed(2)} INR</span>
                  </div>
                </div>
              </div>
            </div>

            <div className="modal-footer" style={{ borderTop: '1px solid #e2e8f0', paddingTop: '15px' }}>
              <button onClick={() => window.print()} className="btn" style={{ background: '#0f172a' }}>Print Invoice</button>
              <button onClick={() => setSelectedInvoice(null)} className="btn btn-secondary" style={{ color: '#1e293b' }}>Close</button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL 5: AADHAAR OTP CONFIRMATION */}
      {showOtpModal && verifyingDoc && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card" style={{ maxWidth: '500px' }}>
            <div className="modal-header">
              <h3 className="modal-title">Enter Aadhaar Verification OTP</h3>
              <button onClick={() => {
                setShowOtpModal(false);
                setVerifyingDoc(null);
              }} className="modal-close">×</button>
            </div>
            <form onSubmit={handleVerifyOtpSubmit}>
              <div style={{ marginBottom: '15px', fontSize: '14px', color: '#e2e8f0' }}>
                <p>An OTP code has been requested for Aadhaar: <code>{verifyingDoc.document_number_masked}</code>.</p>
                <p style={{ fontStyle: 'italic', color: '#cbd5e1' }}>In Mock Sandbox Mode, use code <strong>123456</strong>.</p>
              </div>
              <div className="form-group" style={{ marginBottom: '15px' }}>
                <label className="form-label">OTP Verification Code</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="Enter 6-digit OTP code"
                  value={otpCode}
                  onChange={(e) => setOtpCode(e.target.value)}
                  maxLength={6}
                  required
                />
              </div>
              <div className="modal-footer">
                <button type="button" onClick={() => {
                  setShowOtpModal(false);
                  setVerifyingDoc(null);
                }} className="btn btn-secondary">Cancel</button>
                <button type="submit" className="btn">Verify Aadhaar</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 6: AADHAAR MANUAL OVERRIDE FALLBACK */}
      {showFallbackModal && verifyingDoc && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card" style={{ maxWidth: '500px' }}>
            <div className="modal-header">
              <h3 className="modal-title">Manual Identity Override</h3>
              <button onClick={() => {
                setShowFallbackModal(false);
                setVerifyingDoc(null);
              }} className="modal-close">×</button>
            </div>
            <form onSubmit={handleManualFallbackSubmit}>
              <div style={{ marginBottom: '15px', fontSize: '14px', color: '#e2e8f0' }}>
                <p>Provide the supervisor reason for bypassing Sandbox Aadhaar verification for document: <code>{verifyingDoc.document_number_masked}</code>.</p>
              </div>
              <div className="form-group" style={{ marginBottom: '15px' }}>
                <label className="form-label">Override Reason</label>
                <textarea
                  className="form-input"
                  rows={3}
                  placeholder="e.g. Sandbox API Down - verified physical card copy"
                  value={fallbackReason}
                  onChange={(e) => setFallbackReason(e.target.value)}
                  required
                />
              </div>
              <div className="modal-footer">
                <button type="button" onClick={() => {
                  setShowFallbackModal(false);
                  setVerifyingDoc(null);
                }} className="btn btn-secondary">Cancel</button>
                <button type="submit" className="btn" style={{ background: '#d97706', borderColor: '#d97706' }}>Apply Override</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 7: IDENTITY VERIFICATION LOGS */}
      {showLogsModal && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card" style={{ maxWidth: '800px', width: '90%' }}>
            <div className="modal-header">
              <h3 className="modal-title">Identity Verification Logs</h3>
              <button onClick={() => {
                setShowLogsModal(false);
                setVerifLogs([]);
              }} className="modal-close">×</button>
            </div>
            <div className="table-wrapper" style={{ maxHeight: '400px', overflowY: 'auto' }}>
              <table className="table">
                <thead>
                  <tr>
                    <th>Timestamp</th>
                    <th>Action</th>
                    <th>Status</th>
                    <th>Provider</th>
                    <th>Ref ID</th>
                    <th>Logs / Reason Details</th>
                    <th>Operator</th>
                  </tr>
                </thead>
                <tbody>
                  {verifLogs.length === 0 ? (
                    <tr>
                      <td colSpan={7} style={{ textAlign: 'center', color: '#9ca3af', fontStyle: 'italic' }}>No verification attempts logged.</td>
                    </tr>
                  ) : (
                    verifLogs.map((log) => (
                      <tr key={log.id}>
                        <td>{new Date(log.created_at).toLocaleString()}</td>
                        <td><span style={{ fontSize: '11px', textTransform: 'uppercase', color: '#a78bfa', fontWeight: '600' }}>{log.action}</span></td>
                        <td>
                          <span className={`room-status-badge`} style={{ margin: 0, background: log.status === 'success' ? 'var(--status-available)' : 'var(--status-occupied)', color: '#fff' }}>
                            {log.status}
                          </span>
                        </td>
                        <td>{log.provider}</td>
                        <td><code>{log.reference_id || 'N/A'}</code></td>
                        <td style={{ fontSize: '12px', maxWidth: '250px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }} title={log.details_json}>
                          {log.details_json || 'No extra info'}
                        </td>
                        <td>{log.creator_name || 'System'}</td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
            <div className="modal-footer" style={{ marginTop: '15px' }}>
              <button onClick={() => {
                setShowLogsModal(false);
                setVerifLogs([]);
              }} className="btn btn-secondary">Close</button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL 8: INVENTORY STOCK ADJUSTMENT */}
      {showAdjustModal && selectedHkItem && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card" style={{ maxWidth: '500px' }}>
            <div className="modal-header">
              <h3 className="modal-title">Manual Stock Adjustment - {selectedHkItem.name}</h3>
              <button onClick={() => {
                setShowAdjustModal(false);
                setSelectedHkItem(null);
              }} className="modal-close">×</button>
            </div>
            <form onSubmit={handleAdjustStockSubmit}>
              <div style={{ marginBottom: '15px', fontSize: '14px', color: '#e2e8f0' }}>
                <p>Current stock balance is <strong>{parseFloat(selectedHkItem.current_stock).toFixed(2)} {selectedHkItem.unit_of_measure}</strong> with average unit cost <strong>{parseFloat(selectedHkItem.average_unit_cost).toFixed(2)} INR</strong>.</p>
              </div>
              <div className="form-group" style={{ marginBottom: '12px' }}>
                <label className="form-label">Quantity Adjustment (use negative for deductions)</label>
                <input
                  type="number"
                  step="0.0001"
                  className="form-input"
                  placeholder="e.g. 10.00 or -5.00"
                  value={adjustQty}
                  onChange={(e) => setAdjustQty(e.target.value)}
                  required
                />
              </div>
              <div className="form-group" style={{ marginBottom: '12px' }}>
                <label className="form-label">Unit Cost (applicable for positive adjustments)</label>
                <input
                  type="number"
                  step="0.01"
                  className="form-input"
                  placeholder="Unit cost in INR"
                  value={adjustCost}
                  onChange={(e) => setAdjustCost(e.target.value)}
                />
              </div>
              <div className="form-group" style={{ marginBottom: '15px' }}>
                <label className="form-label">Adjustment Reason / Notes</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="e.g. Damage deduction or manual count correction"
                  value={adjustReason}
                  onChange={(e) => setAdjustReason(e.target.value)}
                  required
                />
              </div>
              <div className="modal-footer">
                <button type="button" onClick={() => {
                  setShowAdjustModal(false);
                  setSelectedHkItem(null);
                }} className="btn btn-secondary">Cancel</button>
                <button type="submit" className="btn">Post Adjustment</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 9: STOCK AUDIT LEDGER */}
      {showLedgerModal && selectedHkItem && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card" style={{ maxWidth: '800px', width: '90%' }}>
            <div className="modal-header">
              <h3 className="modal-title">Stock Ledger Log - {selectedHkItem.name}</h3>
              <button onClick={() => {
                setShowLedgerModal(false);
                setSelectedHkItem(null);
                setItemLedger([]);
              }} className="modal-close">×</button>
            </div>
            <div className="table-wrapper" style={{ maxHeight: '400px', overflowY: 'auto' }}>
              <table className="table">
                <thead>
                  <tr>
                    <th>Timestamp</th>
                    <th>Type</th>
                    <th>Qty Delta</th>
                    <th>Unit Cost</th>
                    <th>Resulting Stock</th>
                    <th>Resulting Cost</th>
                    <th>Operator</th>
                    <th>Details / Remarks</th>
                  </tr>
                </thead>
                <tbody>
                  {itemLedger.length === 0 ? (
                    <tr>
                      <td colSpan={8} style={{ textAlign: 'center', color: '#9ca3af', fontStyle: 'italic' }}>No ledger transaction history found.</td>
                    </tr>
                  ) : (
                    itemLedger.map((log) => (
                      <tr key={log.id}>
                        <td>{new Date(log.created_at).toLocaleString()}</td>
                        <td>
                          <span style={{ 
                            fontSize: '11px', 
                            textTransform: 'uppercase', 
                            color: log.transaction_type === 'goods_receipt' ? 'var(--status-available)' : '#cbd5e1', 
                            fontWeight: '600' 
                          }}>{log.transaction_type}</span>
                        </td>
                        <td>
                          <strong style={{ color: parseFloat(log.quantity) > 0 ? 'var(--status-available)' : 'var(--status-occupied)' }}>
                            {parseFloat(log.quantity) > 0 ? `+${parseFloat(log.quantity).toFixed(2)}` : parseFloat(log.quantity).toFixed(2)}
                          </strong>
                        </td>
                        <td>{parseFloat(log.unit_cost).toFixed(2)} INR</td>
                        <td>{parseFloat(log.resulting_stock).toFixed(2)}</td>
                        <td>{parseFloat(log.resulting_avg_cost).toFixed(2)} INR</td>
                        <td>{log.creator_name}</td>
                        <td style={{ fontSize: '12px' }}>{log.notes || '-'}</td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
            <div className="modal-footer" style={{ marginTop: '15px' }}>
              <button onClick={() => {
                setShowLedgerModal(false);
                setSelectedHkItem(null);
                setItemLedger([]);
              }} className="btn btn-secondary">Close</button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL 10: CREATE STOCK ITEM */}
      {showCreateItemModal && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card" style={{ maxWidth: '500px' }}>
            <div className="modal-header">
              <h3 className="modal-title">Register New Stock Item</h3>
              <button onClick={() => setShowCreateItemModal(false)} className="modal-close">×</button>
            </div>
            <form onSubmit={handleCreateInventoryItem}>
              <div className="form-group" style={{ marginBottom: '12px' }}>
                <label className="form-label">SKU / Item Code</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="e.g. LINEN-01 or MILK-LIT"
                  value={newItemSku}
                  onChange={(e) => setNewItemSku(e.target.value)}
                  required
                />
              </div>
              <div className="form-group" style={{ marginBottom: '12px' }}>
                <label className="form-label">Item Name</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="e.g. Single Bed Bedsheet or Whole Milk"
                  value={newItemName}
                  onChange={(e) => setNewItemName(e.target.value)}
                  required
                />
              </div>
              <div className="form-group" style={{ marginBottom: '12px' }}>
                <label className="form-label">Category</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="e.g. Housekeeping, Kitchen, Laundry"
                  value={newItemCategory}
                  onChange={(e) => setNewItemCategory(e.target.value)}
                />
              </div>
              <div className="form-group" style={{ marginBottom: '12px' }}>
                <label className="form-label">Unit of Measure (UOM)</label>
                <select className="select-dropdown" value={newItemUom} onChange={(e) => setNewItemUom(e.target.value)}>
                  <option value="Pcs">Pieces (Pcs)</option>
                  <option value="Kg">Kilograms (Kg)</option>
                  <option value="Litre">Litres (Litre)</option>
                  <option value="Box">Boxes (Box)</option>
                </select>
              </div>
              <div className="form-group" style={{ marginBottom: '15px' }}>
                <label className="form-label">Minimum Stock Alert Level</label>
                <input
                  type="number"
                  step="0.01"
                  className="form-input"
                  value={newItemMinStock}
                  onChange={(e) => setNewItemMinStock(e.target.value)}
                  required
                />
              </div>
              <div className="modal-footer">
                <button type="button" onClick={() => setShowCreateItemModal(false)} className="btn btn-secondary">Cancel</button>
                <button type="submit" className="btn">Save Item</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 11: REGISTER VENDOR */}
      {showCreateVendorModal && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card" style={{ maxWidth: '500px' }}>
            <div className="modal-header">
              <h3 className="modal-title">Register Supplier Vendor</h3>
              <button onClick={() => setShowCreateVendorModal(false)} className="modal-close">×</button>
            </div>
            <form onSubmit={handleCreateVendor}>
              <div className="form-group" style={{ marginBottom: '12px' }}>
                <label className="form-label">Vendor / Supplier Name</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="e.g. Metro Wholesale Supplier"
                  value={newVendorName}
                  onChange={(e) => setNewVendorName(e.target.value)}
                  required
                />
              </div>
              <div className="form-group" style={{ marginBottom: '12px' }}>
                <label className="form-label">Contact Person Name</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="e.g. Ramesh Kumar"
                  value={newVendorContact}
                  onChange={(e) => setNewVendorContact(e.target.value)}
                />
              </div>
              <div className="form-group" style={{ marginBottom: '12px' }}>
                <label className="form-label">Phone Number</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="e.g. +91 9876543210"
                  value={newVendorPhone}
                  onChange={(e) => setNewVendorPhone(e.target.value)}
                />
              </div>
              <div className="form-group" style={{ marginBottom: '12px' }}>
                <label className="form-label">Email Address</label>
                <input
                  type="email"
                  className="form-input"
                  placeholder="e.g. sales@metro.com"
                  value={newVendorEmail}
                  onChange={(e) => setNewVendorEmail(e.target.value)}
                />
              </div>
              <div className="form-group" style={{ marginBottom: '12px' }}>
                <label className="form-label">Office Address</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="City, State"
                  value={newVendorAddress}
                  onChange={(e) => setNewVendorAddress(e.target.value)}
                />
              </div>
              <div className="form-group" style={{ marginBottom: '15px' }}>
                <label className="form-label">GSTIN / Tax ID Number</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="e.g. 07AAAAA1111A1Z1"
                  value={newVendorGst}
                  onChange={(e) => setNewVendorGst(e.target.value)}
                />
              </div>
              <div className="modal-footer">
                <button type="button" onClick={() => setShowCreateVendorModal(false)} className="btn btn-secondary">Cancel</button>
                <button type="submit" className="btn">Register Vendor</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 12: CREATE PURCHASE ORDER */}
      {showCreatePoModal && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card" style={{ maxWidth: '650px', width: '90%' }}>
            <div className="modal-header">
              <h3 className="modal-title">Generate Purchase Order</h3>
              <button onClick={() => setShowCreatePoModal(false)} className="modal-close">×</button>
            </div>
            <form onSubmit={handleCreatePoSubmit}>
              <div className="form-group" style={{ marginBottom: '12px' }}>
                <label className="form-label">Select Vendor Supplier</label>
                <select className="select-dropdown" value={newPoVendorId} onChange={(e) => setNewPoVendorId(e.target.value)} required>
                  <option value="">-- Choose Vendor --</option>
                  {vendors.map(v => (
                    <option key={v.id} value={v.id}>{v.name}</option>
                  ))}
                </select>
              </div>

              <div style={{ marginBottom: '12px' }}>
                <label className="form-label">PO Items List</label>
                {newPoItems.map((item, idx) => (
                  <div key={idx} style={{ display: 'flex', gap: '8px', marginBottom: '8px', alignItems: 'center' }}>
                    <select 
                      className="select-dropdown" 
                      value={item.inventory_item_id}
                      onChange={(e) => {
                        const updated = [...newPoItems];
                        updated[idx].inventory_item_id = e.target.value;
                        setNewPoItems(updated);
                      }}
                      required
                      style={{ flex: 2 }}
                    >
                      <option value="">-- Choose Stock Item --</option>
                      {inventoryItems.map(ii => (
                        <option key={ii.id} value={ii.id}>{ii.name} ({ii.sku})</option>
                      ))}
                    </select>
                    <input
                      type="number"
                      placeholder="Qty"
                      className="form-input"
                      required
                      value={item.quantity}
                      onChange={(e) => {
                        const updated = [...newPoItems];
                        updated[idx].quantity = e.target.value;
                        setNewPoItems(updated);
                      }}
                      style={{ flex: 1, minWidth: '70px' }}
                    />
                    <input
                      type="number"
                      placeholder="Unit Cost"
                      className="form-input"
                      required
                      value={item.unit_price}
                      onChange={(e) => {
                        const updated = [...newPoItems];
                        updated[idx].unit_price = e.target.value;
                        setNewPoItems(updated);
                      }}
                      style={{ flex: 1, minWidth: '90px' }}
                    />
                    {newPoItems.length > 1 && (
                      <button type="button" onClick={() => setNewPoItems(newPoItems.filter((_, i) => i !== idx))} style={{ background: '#ef4444', color: '#fff', border: 'none', padding: '0 8px', borderRadius: '4px', cursor: 'pointer', height: '36px' }}>×</button>
                    )}
                  </div>
                ))}
                <button type="button" onClick={() => setNewPoItems([...newPoItems, { inventory_item_id: '', quantity: '', unit_price: '' }])} className="btn btn-secondary btn-sm" style={{ marginTop: '5px' }}>+ Add Row</button>
              </div>

              <div className="form-group" style={{ marginBottom: '15px' }}>
                <label className="form-label">Order Notes / Terms</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="e.g. Net 30, deliver by Friday"
                  value={newPoNotes}
                  onChange={(e) => setNewPoNotes(e.target.value)}
                />
              </div>

              <div className="modal-footer">
                <button type="button" onClick={() => setShowCreatePoModal(false)} className="btn btn-secondary">Cancel</button>
                <button type="submit" className="btn">Request Approval</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 13: LOG GOODS RECEIPT NOTE (GRN) */}
      {showGrnModal && selectedPo && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card" style={{ maxWidth: '750px', width: '95%' }}>
            <div className="modal-header">
              <h3 className="modal-title">Receive Order Goods - PO {selectedPo.po_number}</h3>
              <button onClick={() => {
                setShowGrnModal(false);
                setSelectedPo(null);
              }} className="modal-close">×</button>
            </div>
            <form onSubmit={handleGrnSubmit}>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px', marginBottom: '15px' }}>
                <div className="form-group">
                  <label className="form-label">Received Date</label>
                  <input
                    type="date"
                    className="form-input"
                    value={receivedDate}
                    onChange={(e) => setReceivedDate(e.target.value)}
                    required
                  />
                </div>
                <div className="form-group">
                  <label className="form-label">Receipt Remarks</label>
                  <input
                    type="text"
                    className="form-input"
                    placeholder="e.g. Checked & verified batch stock"
                    value={grnNotes}
                    onChange={(e) => setGrnNotes(e.target.value)}
                  />
                </div>
              </div>

              <div style={{ marginBottom: '15px' }}>
                <label className="form-label" style={{ fontWeight: 'bold' }}>Items Received & Batch Allocations</label>
                {grnItems.map((item, idx) => (
                  <div key={idx} style={{ background: 'rgba(0,0,0,0.1)', padding: '10px', borderRadius: '6px', marginBottom: '10px' }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '5px', fontWeight: 'bold', fontSize: '13px' }}>
                      <span>{item.item_name}</span>
                      <span>Target PO: {parseFloat(selectedPo.items[idx]?.quantity).toFixed(2)}</span>
                    </div>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr 1fr', gap: '6px' }}>
                      <div>
                        <label style={{ fontSize: '11px', display: 'block', marginBottom: '2px' }}>Recv Qty</label>
                        <input
                          type="number"
                          className="form-input"
                          style={{ padding: '4px', fontSize: '12px' }}
                          value={item.quantity}
                          onChange={(e) => {
                            const updated = [...grnItems];
                            updated[idx].quantity = e.target.value;
                            setGrnItems(updated);
                          }}
                          required
                        />
                      </div>
                      <div>
                        <label style={{ fontSize: '11px', display: 'block', marginBottom: '2px' }}>Actual Cost</label>
                        <input
                          type="number"
                          className="form-input"
                          style={{ padding: '4px', fontSize: '12px' }}
                          value={item.unit_cost}
                          onChange={(e) => {
                            const updated = [...grnItems];
                            updated[idx].unit_cost = e.target.value;
                            setGrnItems(updated);
                          }}
                          required
                        />
                      </div>
                      <div>
                        <label style={{ fontSize: '11px', display: 'block', marginBottom: '2px' }}>Batch Code (Opt)</label>
                        <input
                          type="text"
                          className="form-input"
                          style={{ padding: '4px', fontSize: '12px' }}
                          placeholder="e.g. B-01"
                          value={item.batch_number}
                          onChange={(e) => {
                            const updated = [...grnItems];
                            updated[idx].batch_number = e.target.value;
                            setGrnItems(updated);
                          }}
                        />
                      </div>
                      <div>
                        <label style={{ fontSize: '11px', display: 'block', marginBottom: '2px' }}>Expiry Date (Opt)</label>
                        <input
                          type="date"
                          className="form-input"
                          style={{ padding: '4px 2px', fontSize: '11px' }}
                          value={item.expiry_date}
                          onChange={(e) => {
                            const updated = [...grnItems];
                            updated[idx].expiry_date = e.target.value;
                            setGrnItems(updated);
                          }}
                        />
                      </div>
                    </div>
                  </div>
                ))}
              </div>

              <div className="modal-footer">
                <button type="button" onClick={() => {
                  setShowGrnModal(false);
                  setSelectedPo(null);
                }} className="btn btn-secondary">Cancel</button>
                <button type="submit" className="btn">Verify & Settle Stock GRN</button>
              </div>
            </form>
          </div>
        </div>
      )}

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
      {/* MODAL 14: KITCHEN MENU ITEM CREATE */}
      {showCreateMenuItemModal && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card" style={{ maxWidth: '500px' }}>
            <div className="modal-header">
              <h3 className="modal-title">Register Kitchen Menu Item</h3>
              <button onClick={() => setShowCreateMenuItemModal(false)} className="modal-close">×</button>
            </div>
            <form onSubmit={handleCreateMenuItem}>
              <div className="form-group" style={{ marginBottom: '12px' }}>
                <label className="form-label">Dish Name</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="e.g. Masala Chai or Veg Club Sandwich"
                  value={newMenuItemName}
                  onChange={(e) => setNewMenuItemName(e.target.value)}
                  required
                />
              </div>
              <div className="form-group" style={{ marginBottom: '12px' }}>
                <label className="form-label">Description / Remarks</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="e.g. Served with French fries & ketchup"
                  value={newMenuItemDesc}
                  onChange={(e) => setNewMenuItemDesc(e.target.value)}
                />
              </div>
              <div className="form-group" style={{ marginBottom: '15px' }}>
                <label className="form-label">Selling Price to Guest (INR)</label>
                <input
                  type="number"
                  step="0.01"
                  className="form-input"
                  value={newMenuItemPrice}
                  onChange={(e) => setNewMenuItemPrice(e.target.value)}
                  required
                />
              </div>
              <div className="modal-footer">
                <button type="button" onClick={() => setShowCreateMenuItemModal(false)} className="btn btn-secondary">Cancel</button>
                <button type="submit" className="btn">Save Menu Item</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 15: CONFIGURE RECIPE */}
      {showRecipeModal && selectedKItem && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card" style={{ maxWidth: '650px', width: '90%' }}>
            <div className="modal-header">
              <h3 className="modal-title">Configure Recipe & Costing - {selectedKItem.name}</h3>
              <button onClick={() => {
                setShowRecipeModal(false);
                setSelectedKItem(null);
              }} className="modal-close">×</button>
            </div>
            <form onSubmit={handleConfigureRecipeSubmit}>
              <div style={{ marginBottom: '15px' }}>
                <label className="form-label">Required Ingredient Quantities (Per Serving)</label>
                {recipeIngredients.map((item, idx) => (
                  <div key={idx} style={{ display: 'flex', gap: '8px', marginBottom: '8px', alignItems: 'center' }}>
                    <select
                      className="select-dropdown"
                      value={item.inventory_item_id}
                      onChange={(e) => {
                        const updated = [...recipeIngredients];
                        updated[idx].inventory_item_id = e.target.value;
                        setRecipeIngredients(updated);
                      }}
                      required
                      style={{ flex: 2 }}
                    >
                      <option value="">-- Select Inventory Ingredient --</option>
                      {inventoryItems.map(ii => (
                        <option key={ii.id} value={ii.id}>{ii.name} ({ii.sku})</option>
                      ))}
                    </select>
                    <input
                      type="number"
                      step="0.0001"
                      placeholder="Qty Required"
                      className="form-input"
                      required
                      value={item.quantity}
                      onChange={(e) => {
                        const updated = [...recipeIngredients];
                        updated[idx].quantity = e.target.value;
                        setRecipeIngredients(updated);
                      }}
                      style={{ flex: 1, minWidth: '100px' }}
                    />
                    {recipeIngredients.length > 1 && (
                      <button type="button" onClick={() => setRecipeIngredients(recipeIngredients.filter((_, i) => i !== idx))} style={{ background: '#ef4444', color: '#fff', border: 'none', padding: '0 8px', borderRadius: '4px', cursor: 'pointer', height: '36px' }}>×</button>
                    )}
                  </div>
                ))}
                <button type="button" onClick={() => setRecipeIngredients([...recipeIngredients, { inventory_item_id: '', quantity: '' }])} className="btn btn-secondary btn-sm" style={{ marginTop: '5px' }}>+ Add Ingredient</button>
              </div>

              <div className="form-group" style={{ marginBottom: '15px' }}>
                <label className="form-label">Cooking Instructions / Preparation Notes</label>
                <textarea
                  className="form-input"
                  style={{ minHeight: '80px', fontFamily: 'inherit' }}
                  placeholder="e.g. Boil tea leaves with milk for 5 mins, add cardamom."
                  value={recipeInstructions}
                  onChange={(e) => setRecipeInstructions(e.target.value)}
                />
              </div>

              <div className="modal-footer">
                <button type="button" onClick={() => {
                  setShowRecipeModal(false);
                  setSelectedKItem(null);
                }} className="btn btn-secondary">Cancel</button>
                <button type="submit" className="btn">Save Recipe & Costing Sheet</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 16: PLACE KITCHEN ORDER */}
      {showCreateKitchenOrderModal && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card" style={{ maxWidth: '650px', width: '90%' }}>
            <div className="modal-header">
              <h3 className="modal-title">Place Room Service Kitchen Order</h3>
              <button onClick={() => setShowCreateKitchenOrderModal(false)} className="modal-close">×</button>
            </div>
            <form onSubmit={handleCreateKitchenOrder}>
              <div className="form-group" style={{ marginBottom: '12px' }}>
                <label className="form-label">Select Active Guest Stay / Room</label>
                <select className="select-dropdown" value={newKoStayId} onChange={(e) => setNewKoStayId(e.target.value)} required>
                  <option value="">-- Select Active Stay --</option>
                  {stays.filter(s => s.status === 'Active').map(s => (
                    <option key={s.id} value={s.id}>Stay #{s.id} - Room {s.rooms?.[0]?.room_number} ({s.booker_first_name} {s.booker_last_name})</option>
                  ))}
                </select>
              </div>

              <div style={{ marginBottom: '12px' }}>
                <label className="form-label" style={{ display: 'flex', alignItems: 'center', gap: '8px', cursor: 'pointer' }}>
                  <input
                    type="checkbox"
                    checked={newKoMealIncluded}
                    onChange={(e) => setNewKoMealIncluded(e.target.checked)}
                  />
                  Covered under Meal Plan (Include in CP/MAP/AP, no extra charge on checkout folio)
                </label>
              </div>

              <div style={{ marginBottom: '12px' }}>
                <label className="form-label">Menu Items Selection</label>
                {newKoItems.map((item, idx) => (
                  <div key={idx} style={{ display: 'flex', gap: '8px', marginBottom: '8px', alignItems: 'center' }}>
                    <select
                      className="select-dropdown"
                      value={item.kitchen_item_id}
                      onChange={(e) => {
                        const updated = [...newKoItems];
                        updated[idx].kitchen_item_id = e.target.value;
                        setNewKoItems(updated);
                      }}
                      required
                      style={{ flex: 2 }}
                    >
                      <option value="">-- Choose Menu Item --</option>
                      {kitchenItems.map(ki => (
                        <option key={ki.id} value={ki.id}>{ki.name} ({parseFloat(ki.price).toFixed(2)} INR)</option>
                      ))}
                    </select>
                    <input
                      type="number"
                      placeholder="Qty"
                      className="form-input"
                      required
                      value={item.quantity}
                      onChange={(e) => {
                        const updated = [...newKoItems];
                        updated[idx].quantity = e.target.value;
                        setNewKoItems(updated);
                      }}
                      style={{ flex: 1, minWidth: '80px' }}
                    />
                    {newKoItems.length > 1 && (
                      <button type="button" onClick={() => setNewKoItems(newKoItems.filter((_, i) => i !== idx))} style={{ background: '#ef4444', color: '#fff', border: 'none', padding: '0 8px', borderRadius: '4px', cursor: 'pointer', height: '36px' }}>×</button>
                    )}
                  </div>
                ))}
                <button type="button" onClick={() => setNewKoItems([...newKoItems, { kitchen_item_id: '', quantity: '1' }])} className="btn btn-secondary btn-sm" style={{ marginTop: '5px' }}>+ Add Item</button>
              </div>

              <div className="form-group" style={{ marginBottom: '15px' }}>
                <label className="form-label">Preparation Notes / Special Instructions</label>
                <input
                  type="text"
                  className="form-input"
                  placeholder="e.g. Make it extra spicy, serve without onions"
                  value={newKoNotes}
                  onChange={(e) => setNewKoNotes(e.target.value)}
                />
              </div>

              <div className="modal-footer">
                <button type="button" onClick={() => setShowCreateKitchenOrderModal(false)} className="btn btn-secondary">Cancel</button>
                <button type="submit" className="btn">Send to Kitchen</button>
              </div>
            </form>
          </div>
        </div>
      )}
      {/* MODAL 17: CREATE EMPLOYEE */}
      {showCreateEmployeeModal && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card" style={{ maxWidth: '650px', width: '90%' }}>
            <div className="modal-header">
              <h3 className="modal-title">Register Employee Profile</h3>
              <button onClick={() => setShowCreateEmployeeModal(false)} className="modal-close">×</button>
            </div>
            <form onSubmit={handleCreateEmployee}>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', marginBottom: '12px' }}>
                <div className="form-group">
                  <label className="form-label">First Name</label>
                  <input type="text" className="form-input" required value={empFirstName} onChange={(e) => setEmpFirstName(e.target.value)} />
                </div>
                <div className="form-group">
                  <label className="form-label">Last Name</label>
                  <input type="text" className="form-input" required value={empLastName} onChange={(e) => setEmpLastName(e.target.value)} />
                </div>
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', marginBottom: '12px' }}>
                <div className="form-group">
                  <label className="form-label">Employee Code</label>
                  <input type="text" className="form-input" placeholder="e.g. EMP102" required value={empCode} onChange={(e) => setEmpCode(e.target.value)} />
                </div>
                <div className="form-group">
                  <label className="form-label">Status</label>
                  <select className="select-dropdown" value={empStatus} onChange={(e) => setEmpStatus(e.target.value)}>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                  </select>
                </div>
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', marginBottom: '12px' }}>
                <div className="form-group">
                  <label className="form-label">Email Address</label>
                  <input type="email" className="form-input" value={empEmail} onChange={(e) => setEmpEmail(e.target.value)} />
                </div>
                <div className="form-group">
                  <label className="form-label">Phone Number</label>
                  <input type="text" className="form-input" value={empPhone} onChange={(e) => setEmpPhone(e.target.value)} />
                </div>
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', marginBottom: '12px' }}>
                <div className="form-group">
                  <label className="form-label">Department Assignment</label>
                  <select className="select-dropdown" value={empDeptId} onChange={(e) => setEmpDeptId(e.target.value)}>
                    <option value="">-- Choose Department --</option>
                    {departments.map(d => (
                      <option key={d.id} value={d.id}>{d.name} ({d.code})</option>
                    ))}
                  </select>
                </div>
                <div className="form-group">
                  <label className="form-label">Shift Calendar</label>
                  <select className="select-dropdown" value={empShiftId} onChange={(e) => setEmpShiftId(e.target.value)}>
                    <option value="">-- Choose Shift --</option>
                    {shifts.map(s => (
                      <option key={s.id} value={s.id}>{s.name} ({s.start_time.substring(0, 5)} - {s.end_time.substring(0, 5)})</option>
                    ))}
                  </select>
                </div>
              </div>

              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', marginBottom: '12px' }}>
                <div className="form-group">
                  <label className="form-label">Emergency Contact Name</label>
                  <input type="text" className="form-input" placeholder="Name of relative" value={empEmergencyName} onChange={(e) => setEmpEmergencyName(e.target.value)} />
                </div>
                <div className="form-group">
                  <label className="form-label">Emergency Phone</label>
                  <input type="text" className="form-input" placeholder="Relative phone number" value={empEmergencyPhone} onChange={(e) => setEmpEmergencyPhone(e.target.value)} />
                </div>
              </div>

              <div className="form-group" style={{ marginBottom: '15px' }}>
                <label className="form-label">Base Salary (INR/Month) - Restricted View</label>
                <input type="number" step="0.01" placeholder="e.g. 25000.00" className="form-input" value={empSalary} onChange={(e) => setEmpSalary(e.target.value)} />
              </div>

              <div className="modal-footer">
                <button type="button" onClick={() => setShowCreateEmployeeModal(false)} className="btn btn-secondary">Cancel</button>
                <button type="submit" className="btn">Register Profile</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 18: CREATE DEPARTMENT */}
      {showCreateDeptModal && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card" style={{ maxWidth: '450px' }}>
            <div className="modal-header">
              <h3 className="modal-title">Register Department</h3>
              <button onClick={() => setShowCreateDeptModal(false)} className="modal-close">×</button>
            </div>
            <form onSubmit={handleCreateDepartment}>
              <div className="form-group" style={{ marginBottom: '12px' }}>
                <label className="form-label">Department Name</label>
                <input type="text" className="form-input" placeholder="e.g. Food & Beverage" required value={deptName} onChange={(e) => setDeptName(e.target.value)} />
              </div>
              <div className="form-group" style={{ marginBottom: '15px' }}>
                <label className="form-label">Department Code</label>
                <input type="text" className="form-input" placeholder="e.g. FB" required value={deptCode} onChange={(e) => setDeptCode(e.target.value)} />
              </div>
              <div className="modal-footer">
                <button type="button" onClick={() => setShowCreateDeptModal(false)} className="btn btn-secondary">Cancel</button>
                <button type="submit" className="btn">Save Department</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 19: CREATE SHIFT */}
      {showCreateShiftModal && (
        <div className="modal-overlay">
          <div className="glass-panel modal-card" style={{ maxWidth: '450px' }}>
            <div className="modal-header">
              <h3 className="modal-title">Register Shift Schedule</h3>
              <button onClick={() => setShowCreateShiftModal(false)} className="modal-close">×</button>
            </div>
            <form onSubmit={handleCreateShift}>
              <div className="form-group" style={{ marginBottom: '12px' }}>
                <label className="form-label">Shift Name</label>
                <input type="text" className="form-input" placeholder="e.g. Morning Shift" required value={shiftName} onChange={(e) => setShiftName(e.target.value)} />
              </div>
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '10px', marginBottom: '15px' }}>
                <div className="form-group">
                  <label className="form-label">Start Time</label>
                  <input type="time" className="form-input" required value={shiftStart} onChange={(e) => setShiftStart(e.target.value)} />
                </div>
                <div className="form-group">
                  <label className="form-label">End Time</label>
                  <input type="time" className="form-input" required value={shiftEnd} onChange={(e) => setShiftEnd(e.target.value)} />
                </div>
              </div>
              <div className="modal-footer">
                <button type="button" onClick={() => setShowCreateShiftModal(false)} className="btn btn-secondary">Cancel</button>
                <button type="submit" className="btn">Save Shift</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

export default App;
