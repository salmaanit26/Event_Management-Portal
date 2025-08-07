# 🎯 IRA Registration System Flow - Complete Guide

## 📋 **How Students Register for IRA Slots:**

### 1. **Student Registration Process:**
```
Student Login → Dashboard → "🎯 IRA Registration" Button → ira_register.php
     ↓
Select Event → View Available Slots → Choose Time Slot → Register
     ↓
Registration saved to database → Student sees "Pending Review" status
```

### 2. **Where Students Register:**
- **Main Entry:** Dashboard → **"🎯 IRA Registration"** button
- **Direct Link:** `ira_register.php`
- **Event-Specific:** Dashboard events → **"Register for IRA"** button
- **Alternative Routes:** IRA page → "Book Slot" links

### 3. **Registration Data Storage:**
All registrations are stored in `ira_registered_students` table with:
- Student information (name, email, department, year)
- Selected slot ID and event ID
- Registration status ("Pending Review" → "Eligible"/"Not Eligible")
- Faculty evaluation details

---

## 🛠️ **How Admin Manages Slot Registrations:**

### 1. **Admin Access Points:**
```
Admin Dashboard → Multiple Entry Points:
├── 🛠️ Admin Panel (admin_management.php)
├── ⏰ Manage Slots (manage_slots.php)
└── 🎯 Manage IRA (ira_page.php)
```

### 2. **Slot-Specific Management:**
```
Admin Login → Manage Slots → Click "View Registrations" → slot_registrations.php
     ↓
See ALL students registered for THAT specific slot:
├── Student details (name, email, department, year)
├── Registration status and faculty evaluation
├── Registration date and time
└── DELETE button for each student
```

### 3. **Admin Can See:**
✅ **Who registered for each slot** - Complete student list
✅ **Student details** - Name, email, department, year
✅ **Registration status** - Pending/Eligible/Not Eligible  
✅ **Faculty evaluation** - Status and remarks
✅ **Capacity tracking** - X/Y students registered
✅ **Registration timeline** - When each student registered

### 4. **Admin Delete Options:**
✅ **Individual registrations** - Remove specific students from slots
✅ **Bulk management** - Through admin panel
✅ **Slot deletion** - Delete entire slots (if no registrations)

---

## 🔄 **Complete System Flow:**

### **Student Side:**
1. **Login** as student
2. **Go to Dashboard** → Click "🎯 IRA Registration"
3. **Select Event** from approved IRA events
4. **View Available Slots** with faculty assignments
5. **Choose Time Slot** and register
6. **Status shows "Pending Review"**
7. **Faculty evaluates** → Status updates to "Eligible"/"Not Eligible"

### **Admin Side:**
1. **Login** as admin
2. **Multiple management options:**
   - **Admin Panel:** View all registrations across all slots
   - **Manage Slots:** Create slots, view slot-specific registrations
   - **IRA Page:** General IRA management
3. **Click "View Registrations"** next to any slot
4. **See complete list** of students registered for that slot
5. **Manage registrations:**
   - View student details
   - Check evaluation status
   - Delete problematic registrations
   - Monitor capacity

### **Faculty Side:**
1. **Login** as faculty
2. **Faculty Dashboard** shows students from assigned slots
3. **Evaluate students** → Updates visible to admin and students

---

## 📊 **Data Relationships:**

```
Events (IRA=YES) → Slots (with assigned faculty) → Student Registrations
        ↓                    ↓                           ↓
Admin creates IRA    Admin assigns faculty     Students choose slots
    events              to time slots           and register
        ↓                    ↓                           ↓
Students see         Faculty evaluates         Admin can delete
available events     registered students       any registration
```

---

## 🎯 **Answer to Your Question:**

**Where students register:** `ira_register.php` (accessed via Dashboard "🎯 IRA Registration")

**Where admin sees registrations:** 
- **General:** `admin_management.php` (all registrations)
- **Slot-specific:** `slot_registrations.php?slot_id=X` (per slot)
- **Access:** Manage Slots → "View Registrations" button

**Admin delete access:** 
✅ Can delete any student registration from any slot
✅ Slot-specific management shows who registered for each time slot
✅ Complete student data with evaluation status
✅ Confirmation dialogs prevent accidental deletion

The system now provides **complete visibility** and **management control** for admin to see exactly who registered for each slot and remove them if needed!
