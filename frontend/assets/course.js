/* ── In-memory database ─────────────────────── */
let users = [
  { id: 1, name: "Admin User",    email: "admin@greenfield.edu", password: "admin123", role: "admin"   },
  { id: 2, name: "Brenda Atieno", email: "brenda@greenfield.edu", password: "brenda123", role: "student" },
  { id: 3, name: "George Kmau",   email: "george@greenfield.edu", password: "george123", role: "student" },
  { id: 4, name: "Test User",     email: "muthem116@gmail.com",  password: "test1234", role: "student" },
];

let courses = [
  { id:1, code:"CS101",   title:"Introduction to Computer Science", desc:"Fundamental concepts of programming and problem solving.",      credits:3, capacity:30, enrolled:12, instructor:"Dr. Barini",   schedule:"Mon/Wed 9:00–10:30" },
  { id:2, code:"CS201",   title:"Data Structures",                  desc:"Arrays, linked lists, trees, graphs, and algorithm analysis.",   credits:3, capacity:25, enrolled:25, instructor:"Md.Gracel",    schedule:"Tue/Thu 11:00–1:30" },
  { id:3, code:"CS301",   title:"Internet Application programming",         desc:"HTML, CSS, JavaScript, and server-side programming.",            credits:3, capacity:30, enrolled:18, instructor:"Prof. Mwenda", schedule:"Mon/Wed/Fri 14:00–15:00" },
  { id:4, code:"SMA2200", title:"Calculus I",                       desc:"Limits, derivatives, and integrals.",                            credits:4, capacity:35, enrolled:30, instructor:"Prof.Okello",      schedule:"Mon/Wed/Fri 10:00–11:00" },
  { id:5, code:"SMA2100",  title:"System design and analysis",              desc:"system analyst.",
  credits:3, capacity:28, enrolled:10, instructor:"Prof. Smith",  schedule:"Tue/Thu 9:00–10:30" },
  { id:6, code:"BUS201",  title:"Introduction to Abstract Algebra",         desc:"Binary operations on sets, groups, proofs.",       credits:3, capacity:40, enrolled: 5, instructor:"Mr. Mwai",  schedule:"Wed/Fri 13:00–14:30" },
];

let registrations = [
  { id:1, userId:2, courseId:1 },
  { id:2, userId:2, courseId:3 },
  { id:3, userId:3, courseId:1 },
  { id:4, userId:3, courseId:4 },
];

let currentUser = null;
let nextUserId  = 10;
let nextCourseId = 10;
let nextRegId    = 10;
let currentPage  = "";
let authTab      = "login";


const $ = id => document.getElementById(id);

function capColor(pct) {
  if (pct >= 1)    return "#f04058";
  if (pct >= 0.8)  return "#f5a623";
  return "#0fba87";
}

function showToast(msg, type = "success") {
  const t = $("toast");
  t.textContent = msg;
  t.className   = type === "error" ? "error" : "";
  t.classList.remove("hidden");
  setTimeout(() => t.classList.add("hidden"), 3000);
}

function capBar(enrolled, capacity) {
  const pct = capacity ? enrolled / capacity : 0;
  const col  = capColor(pct);
  return `
    <div class="cap-row">
      <span>Capacity</span>
      <span>${enrolled} / ${capacity}</span>
    </div>
    <div class="cap-track">
      <div class="cap-fill" style="width:${Math.min(pct,1)*100}%;background:${col}"></div>
    </div>`;
}

function myEnrolledIds() {
  return new Set(registrations.filter(r => r.userId === currentUser.id).map(r => r.courseId));
}


function switchTab(tab) {
  authTab = tab;
  document.querySelectorAll(".tab-btn").forEach((b, i) => {
    b.classList.toggle("active", (i === 0 && tab === "login") || (i === 1 && tab === "register"));
  });
  $("field-name").style.display    = tab === "register" ? "block" : "none";
  $("auth-btn-label").textContent  = tab === "register" ? "Create Account" : "Sign In";
  $("auth-alert").classList.add("hidden");
}

function showAuthAlert(msg) {
  const el = $("auth-alert");
  el.textContent = msg;
  el.className   = "alert alert-error";
}

function submitAuth() {
  const email = $("auth-email").value.trim();
  const pass  = $("auth-password").value;

  if (authTab === "login") {
    const user = users.find(u => u.email === email && u.password === pass);
    if (!user) return showAuthAlert("Invalid email or password. Please try again.");
    loginSuccess(user);
  } else {
    const name = $("reg-name").value.trim();
    if (!name || !email || !pass) return showAuthAlert("All fields are required.");
    if (pass.length < 8)          return showAuthAlert("Password must be at least 8 characters.");
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email))  return showAuthAlert("Please enter a valid email address.");
    if (users.find(u => u.email === email)) return showAuthAlert("This email is already registered.");

    const newUser = { id: nextUserId++, name, email, password: pass, role: "student" };
    users.push(newUser);
    switchTab("login");
    $("auth-email").value    = email;
    $("auth-password").value = "";
    const el = $("auth-alert");
    el.textContent = "Account created! You can now sign in.";
    el.className   = "alert alert-success";
  }
}

function loginSuccess(user) {
  currentUser = user;
  $("login-page").classList.add("hidden");
  $("app").classList.remove("hidden");

  $("nav-username").textContent  = user.name;
  $("nav-role-badge").textContent = user.role;
  $("nav-role-badge").className  = "role-badge " + user.role;

  if (user.role === "admin") {
    $("student-nav").classList.add("hidden");
    $("admin-nav").classList.remove("hidden");
    showPage("manage");
  } else {
    $("student-nav").classList.remove("hidden");
    $("admin-nav").classList.add("hidden");
    showPage("browse");
  }
}

function logout() {
  currentUser = null;
  $("app").classList.add("hidden");
  $("login-page").classList.remove("hidden");
  $("auth-email").value    = "";
  $("auth-password").value = "";
  $("auth-alert").classList.add("hidden");
  switchTab("login");
}


function showPage(page) {
  currentPage = page;
  ["browse","my-courses","manage","registrations","xml"].forEach(p => {
    $("page-" + p).classList.add("hidden");
  });
  $("page-" + page).classList.remove("hidden");

  
  const navEl = currentUser?.role === "admin" ? $("admin-nav") : $("student-nav");
  navEl.querySelectorAll(".nav-link").forEach((btn, i) => {
    const pages = currentUser?.role === "admin"
      ? ["manage","registrations","xml"]
      : ["browse","my-courses"];
    btn.classList.toggle("active", pages[i] === page);
  });

  // Render the page
  if (page === "browse")         renderBrowse();
  if (page === "my-courses")     renderMyCourses();
  if (page === "manage")         renderManage();
  if (page === "registrations")  renderRegistrations();
  if (page === "xml")            renderXML();
}


function renderBrowse() {
  const enrolled = myEnrolledIds();
  const query    = ($("browse-search")?.value || "").toLowerCase();
  const filtered = courses.filter(c =>
    c.title.toLowerCase().includes(query) ||
    c.code.toLowerCase().includes(query) ||
    c.instructor.toLowerCase().includes(query)
  );

  
  const myCredits = courses
    .filter(c => enrolled.has(c.id))
    .reduce((s, c) => s + c.credits, 0);

  $("browse-stats").innerHTML = `
    <div class="stat-card"><div class="stat-label">Available Courses</div><div class="stat-value">${courses.length}</div></div>
    <div class="stat-card"><div class="stat-label">My Enrollments</div><div class="stat-value">${enrolled.size}</div></div>
    <div class="stat-card"><div class="stat-label">My Credits</div><div class="stat-value">${myCredits}</div></div>
  `;

  if (filtered.length === 0) {
    $("browse-grid").innerHTML = `<div class="empty-state"><div class="empty-icon">🔍</div><h3>No courses found</h3><p>Try a different search term.</p></div>`;
    return;
  }

  $("browse-grid").innerHTML = filtered.map(c => {
    const isEnrolled = enrolled.has(c.id);
    const isFull     = c.enrolled >= c.capacity;
    const statusTag  = isEnrolled
      ? `<span class="status-tag enrolled">✓ Enrolled</span>`
      : isFull
        ? `<span class="status-tag full">● Full</span>`
        : `<span class="status-tag open">● Open</span>`;
    const actionBtn  = isEnrolled
      ? `<button class="btn btn-danger btn-sm" onclick="dropCourse(${c.id})">Drop</button>`
      : `<button class="btn btn-primary btn-sm" onclick="enrollCourse(${c.id})" ${isFull?"disabled":""}>
           ${isFull ? "Full" : "Enroll"}
         </button>`;
    return `
      <article class="course-card ${isEnrolled?"is-enrolled":""}">
        <div>
          <div class="course-code">${c.code}</div>
          <div class="course-title">${c.title}</div>
          <p class="course-desc">${c.desc}</p>
        </div>
        <div class="meta-pills">
          <span class="pill">📚 ${c.credits} credits</span>
          <span class="pill">👨‍🏫 ${c.instructor}</span>
          <span class="pill">🕒 ${c.schedule}</span>
        </div>
        ${capBar(c.enrolled, c.capacity)}
        <div class="card-footer">
          ${statusTag}
          <div class="card-actions">${actionBtn}</div>
        </div>
      </article>`;
  }).join("");
}

function enrollCourse(courseId) {
  const course = courses.find(c => c.id === courseId);
  if (!course) return;
  if (course.enrolled >= course.capacity) return showToast("This course is full.", "error");
  if (registrations.find(r => r.userId === currentUser.id && r.courseId === courseId))
    return showToast("Already enrolled.", "error");

  registrations.push({ id: nextRegId++, userId: currentUser.id, courseId });
  course.enrolled = Math.min(course.enrolled + 1, course.capacity);
  showToast(`Enrolled in ${course.code}: ${course.title}`);
  renderBrowse();
}

function dropCourse(courseId) {
  const course = courses.find(c => c.id === courseId);
  const idx    = registrations.findIndex(r => r.userId === currentUser.id && r.courseId === courseId);
  if (idx === -1) return;
  registrations.splice(idx, 1);
  course.enrolled = Math.max(course.enrolled - 1, 0);
  showToast(`Dropped ${course.code}: ${course.title}`);
  if (currentPage === "browse")      renderBrowse();
  if (currentPage === "my-courses")  renderMyCourses();
}

/* ── MY COURSES PAGE ────────────────────────── */
function renderMyCourses() {
  const enrolled = myEnrolledIds();
  const mine     = courses.filter(c => enrolled.has(c.id));
  const credits  = mine.reduce((s, c) => s + c.credits, 0);

  $("my-courses-sub").textContent = `${mine.length} course${mine.length!==1?"s":""} · ${credits} total credits`;

  if (mine.length === 0) {
    $("my-courses-grid").innerHTML = `
      <div class="empty-state">
        <div class="empty-icon">📋</div>
        <h3>No enrollments yet</h3>
        <p>Browse the catalog to get started.</p>
      </div>`;
    return;
  }

  $("my-courses-grid").innerHTML = mine.map(c => `
    <article class="course-card is-enrolled">
      <div>
        <div class="course-code">${c.code}</div>
        <div class="course-title">${c.title}</div>
        <p class="course-desc">${c.desc}</p>
      </div>
      <div class="meta-pills">
        <span class="pill">📚 ${c.credits} credits</span>
        <span class="pill">👨‍🏫 ${c.instructor}</span>
        <span class="pill">🕒 ${c.schedule}</span>
      </div>
      <span class="status-tag enrolled">✓ Enrolled</span>
      <button class="btn btn-danger w-full" onclick="dropCourse(${c.id})">Drop Course</button>
    </article>`
  ).join("");
}

/* ── MANAGE PAGE (Admin) ────────────────────── */
function renderManage() {
  const totalRegs = registrations.length;
  const fullCount = courses.filter(c => c.enrolled >= c.capacity).length;

  $("manage-stats").innerHTML = `
    <div class="stat-card"><div class="stat-label">Total Courses</div><div class="stat-value">${courses.length}</div></div>
    <div class="stat-card"><div class="stat-label">Total Enrollments</div><div class="stat-value">${totalRegs}</div></div>
    <div class="stat-card"><div class="stat-label">Full Courses</div><div class="stat-value">${fullCount}</div></div>
  `;

  const query    = ($("manage-search")?.value || "").toLowerCase();
  const filtered = courses.filter(c =>
    c.title.toLowerCase().includes(query) ||
    c.code.toLowerCase().includes(query)
  );

  if (filtered.length === 0) {
    $("manage-grid").innerHTML = `<div class="empty-state"><div class="empty-icon">📭</div><h3>No courses found</h3></div>`;
    return;
  }

  $("manage-grid").innerHTML = filtered.map(c => {
    const stuCount = registrations.filter(r => r.courseId === c.id).length;
    return `
      <article class="course-card">
        <div>
          <div class="course-code">${c.code}</div>
          <div class="course-title">${c.title}</div>
          <p class="course-desc">${c.desc}</p>
        </div>
        <div class="meta-pills">
          <span class="pill">📚 ${c.credits} cr</span>
          <span class="pill">👨‍🏫 ${c.instructor}</span>
          <span class="pill">🕒 ${c.schedule}</span>
          <span class="pill">👥 ${stuCount} students</span>
        </div>
        ${capBar(c.enrolled, c.capacity)}
        <div class="card-actions">
          <button class="btn btn-ghost" style="flex:1" onclick="openModal(${c.id})">✏️ Edit</button>
          <button class="btn btn-danger btn-sm" onclick="deleteCourse(${c.id})">🗑️</button>
        </div>
      </article>`;
  }).join("");
}

function deleteCourse(id) {
  if (!confirm("Delete this course? All registrations will also be removed.")) return;
  courses = courses.filter(c => c.id !== id);
  registrations = registrations.filter(r => r.courseId !== id);
  showToast("Course deleted.");
  renderManage();
}

/* ── MODAL ──────────────────────────────────── */
function openModal(courseId = null) {
  const modal = $("course-modal");
  $("modal-alert").classList.add("hidden");

  if (courseId) {
    const c = courses.find(c => c.id === courseId);
    $("modal-title").textContent = "Edit Course";
    $("modal-course-id").value   = c.id;
    $("m-code").value            = c.code;
    $("m-title").value           = c.title;
    $("m-desc").value            = c.desc;
    $("m-credits").value         = c.credits;
    $("m-capacity").value        = c.capacity;
    $("m-instructor").value      = c.instructor;
    $("m-schedule").value        = c.schedule;
  } else {
    $("modal-title").textContent = "Add New Course";
    $("modal-course-id").value   = "";
    ["m-code","m-title","m-desc","m-instructor","m-schedule"].forEach(id => $(id).value = "");
    $("m-credits").value  = 3;
    $("m-capacity").value = 30;
  }

  modal.classList.remove("hidden");
}

function closeModal() {
  $("course-modal").classList.add("hidden");
}

function saveCourse() {
  const code       = $("m-code").value.trim().toUpperCase();
  const title      = $("m-title").value.trim();
  const desc       = $("m-desc").value.trim();
  const credits    = parseInt($("m-credits").value);
  const capacity   = parseInt($("m-capacity").value);
  const instructor = $("m-instructor").value.trim();
  const schedule   = $("m-schedule").value.trim();
  const editId     = $("modal-course-id").value;

  if (!code || !title || !instructor || !schedule || !capacity) {
    const al = $("modal-alert");
    al.textContent = "Please fill in all required fields.";
    al.className   = "alert alert-error";
    return;
  }

  if (editId) {
    const c = courses.find(c => c.id == editId);
    Object.assign(c, { code, title, desc, credits, capacity, instructor, schedule });
    showToast("Course updated.");
  } else {
    courses.push({ id: nextCourseId++, code, title, desc, credits, capacity, enrolled: 0, instructor, schedule });
    showToast("Course added.");
  }

  closeModal();
  renderManage();
}

/* ── REGISTRATIONS PAGE (Admin) ─────────────── */
function renderRegistrations() {
  const rows = registrations.map(r => ({
    r,
    user:   users.find(u => u.id === r.userId),
    course: courses.find(c => c.id === r.courseId),
  })).filter(x => x.user && x.course);

  $("regs-sub").textContent = `${rows.length} active enrollment${rows.length!==1?"s":""}`;

  if (rows.length === 0) {
    $("regs-tbody").innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:40px">No registrations found.</td></tr>`;
    return;
  }

  $("regs-tbody").innerHTML = rows.map(({ r, user, course }, i) => `
    <tr>
      <td class="text-muted">${i + 1}</td>
      <td>${user.name}</td>
      <td class="text-muted">${user.email}</td>
      <td>${course.title}</td>
      <td><span class="course-code">${course.code}</span></td>
      <td>${course.credits}</td>
    </tr>`
  ).join("");
}

/* ── XML PAGE (Admin) ───────────────────────── */
function renderXML() {
  const esc = s => String(s)
    .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");

  const lines = [
    `<span class="xml-pi">&lt;?xml version="1.0" encoding="UTF-8"?&gt;</span>`,
    `<span class="xml-tag">&lt;courseCatalog</span> <span class="xml-attr">institution</span>=<span class="xml-value">"Greenfield Institute"</span> <span class="xml-attr">semester</span>=<span class="xml-value">"Fall 2026"</span><span class="xml-tag">&gt;</span>`,
    "",
  ];

  courses.forEach(c => {
    const av     = Math.max(0, c.capacity - c.enrolled);
    const status = c.enrolled >= c.capacity ? "full" : "open";
    const tag    = (name, val) =>
      `  <span class="xml-tag">&lt;${name}&gt;</span><span class="xml-value">${esc(val)}</span><span class="xml-tag">&lt;/${name}&gt;</span>`;

    lines.push(`  <span class="xml-tag">&lt;course&gt;</span>`);
    lines.push(tag("courseId",   c.id));
    lines.push(tag("code",       c.code));
    lines.push(tag("title",      c.title));
    lines.push(tag("description",c.desc));
    lines.push(tag("credits",    c.credits));
    lines.push(tag("capacity",   c.capacity));
    lines.push(tag("enrolled",   c.enrolled));
    lines.push(tag("available",  av));
    lines.push(tag("instructor", c.instructor));
    lines.push(tag("schedule",   c.schedule));
    lines.push(tag("status",     status));
    lines.push(`  <span class="xml-tag">&lt;/course&gt;</span>`);
    lines.push("");
  });

  lines.push(`<span class="xml-tag">&lt;/courseCatalog&gt;</span>`);
  $("xml-viewer").innerHTML = lines.join("\n");
}