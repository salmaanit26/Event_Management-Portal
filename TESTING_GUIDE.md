# Event Management System - Real-Time Testing Guide

## 🚀 **System Status**
✅ **Database:** Cleared all test data - fresh start
✅ **Forms:** Fixed NOT NULL constraint errors  
✅ **Fields:** Removed unwanted fields (estimated_participants, priority, budget)
✅ **Categories:** Updated to Online/Offline/Hybrid format
✅ **Placeholders:** Added to all input fields
✅ **Errors:** Fixed undefined student_id warning

---

## 🧪 **Complete Functional Testing Workflow**

### **Step 1: Login Testing**
**URL:** `http://localhost/Event_management_p/login.php`

**Test Accounts:**
- **Admin:** admin@college.edu / admin123
- **Student:** student@college.edu / student123  
- **Reviewer:** reviewer@college.edu / reviewer123

### **Step 2: Student Workflow Testing**

#### **2.1 Add New Event (Student)**
1. Login as student
2. Navigate to "Add Event" 
3. **Test Required Fields:**
   - Event Name: "AI Workshop 2025"
   - Event Organizer: "Tech Club"
   - Event Date: Select future date
   - Event Time: "14:00"
   - Registration Deadline: Select date before event
   - Domain: "Technical"
   - Event Type: "Workshop"
   - Event Format: "Online" ✅ (New format options)
   - Venue Name: "Virtual Platform"
   - State: "Karnataka"
   - City: "Bangalore"

4. **Optional Fields to Test:**
   - Competition Name: "AI Innovation Challenge"
   - Description: "Advanced AI workshop for students"
   - Upload brochure (test file upload)

5. Click "Submit Event"
6. **Expected:** Success message with event ID

#### **2.2 View Dashboard (Student)**
1. Check dashboard shows submitted event as "Pending"
2. **Expected:** Clean table view, no IRA events yet

#### **2.3 Check Event Status**
1. Navigate to "My Events"
2. **Expected:** Shows submitted event with status tracking

### **Step 3: Admin Workflow Testing**

#### **3.1 Admin Dashboard**
1. Login as admin@college.edu / admin123
2. **Expected:** See all submitted events in clean table format
3. **Test Admin Actions:**

#### **3.2 Approve Event**
1. Find student's submitted event
2. Enter approval remarks: "Great initiative, approved!"
3. Click "Approve"
4. **Expected:** Status changes to "Approved"

#### **3.3 Enable IRA for Event**  
1. For approved event, select "Enable IRA"
2. Enter IRA remarks: "Technical review required"
3. Click "Update IRA"
4. **Expected:** IRA column shows "✅ IRA"

#### **3.4 Create IRA Slots**
1. Navigate to "Manage Slots"
2. Select the IRA-enabled event
3. **Create Slot:**
   - Slot Date: Select future date
   - Time Slot: "09:00-10:00"
   - Hall: "Seminar Hall 1"
   - Assign Faculty: Select reviewer
   - Max Capacity: 10 students
4. Click "Create Slot & Assign Faculty"
5. **Expected:** Slot created successfully

### **Step 4: Student IRA Registration Testing**

#### **4.1 IRA Registration**
1. Login as student
2. Navigate to "IRA Registration"
3. **Expected:** See the IRA-enabled event
4. Click "View Slots & Register"
5. Select available slot and click "Register for Slot"
6. **Expected:** Registration success message

#### **4.2 Dashboard IRA Section**
1. Go back to dashboard
2. **Expected:** See IRA events section with registration option

### **Step 5: Reviewer Workflow Testing**

#### **5.1 Reviewer Dashboard**
1. Login as reviewer@college.edu / reviewer123
2. **Expected:** See assigned events and slots
3. Check IRA registrations for review

### **Step 6: Complete System Integration Testing**

#### **6.1 Test Event Rejection**
1. As admin, create new test event
2. Reject with remarks: "Insufficient details provided"
3. **Expected:** Status shows "Rejected" with remarks visible

#### **6.2 Test Duplicate Prevention**
1. Try submitting event with same name as existing one
2. **Expected:** Error message about duplicate event name

#### **6.3 Test Form Validation**
1. Try submitting form with missing required fields
2. **Expected:** Proper validation errors

#### **6.4 Test File Upload**
1. Upload different file types (.pdf, .jpg, .doc)
2. **Expected:** Files uploaded successfully to uploads/ folder

---

## 🔍 **Key Testing Points**

### **✅ Fields Removed:**
- ❌ Estimated Participants
- ❌ Priority Level  
- ❌ Budget Required

### **✅ New Features:**
- ✅ Event Time field (required)
- ✅ Venue Name field (required)  
- ✅ Event Format: Online/Offline/Hybrid
- ✅ All placeholders added
- ✅ Clean database (no test data)

### **✅ Error Fixes:**
- ✅ NOT NULL constraint resolved
- ✅ Undefined student_id warning fixed
- ✅ Database integrity maintained

---

## 🚨 **Critical Test Cases**

1. **Required Field Validation:** All * marked fields must be filled
2. **Date Validation:** Registration deadline must be before event date
3. **File Upload Security:** Only allowed file types should upload
4. **Duplicate Prevention:** Same event name should be rejected
5. **IRA Workflow:** Complete flow from admin enable → slot creation → student registration
6. **Role-Based Access:** Each role sees appropriate interface
7. **Status Tracking:** Events properly track through Pending → Approved/Rejected
8. **Clean UI:** Professional styling consistent across all pages

---

## 📱 **Mobile Testing**
Test all workflows on mobile devices:
- Form responsiveness
- Navigation accessibility  
- Table scrolling on small screens
- Button interactions

---

## 🎯 **Success Criteria**
- ✅ Students can submit events without errors
- ✅ Admin can approve/reject with remarks
- ✅ IRA system works end-to-end
- ✅ All forms have proper validation
- ✅ Professional UI across all pages
- ✅ No database errors or warnings
- ✅ File uploads work correctly
- ✅ Email notifications ready (if enabled)

**System is now ready for full functional testing!** 🚀
