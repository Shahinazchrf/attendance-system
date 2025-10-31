/* script.js
   Shared JS for:
   - rendering attendance table
   - counting absences & participation
   - coloring rows & generating messages
   - simple form validation and passing new students via localStorage
*/

/* Sample initial data (3 students) */
const sampleStudents = [
  {
    id: "1001", last: "Ahmed", first: "Sara", course: "Web Dev",
    // each session: present (true/false), participated (true/false)
    sessions: [
      {p: true, pa: false},
      {p: true, pa: true},
      {p: false, pa: false},
      {p: false, pa: false},
      {p: false, pa: false},
      {p: false, pa: false}
    ]
  },
  {
    id: "1002", last: "Yacine", first: "Ali", course: "Web Dev",
    sessions: [
      {p: true, pa: true},
      {p: true, pa: true},
      {p: true, pa: true},
      {p: true, pa: true},
      {p: true, pa: true},
      {p: true, pa: true}
    ]
  },
  {
    id: "1003", last: "Houcine", first: "Rania", course: "Web Dev",
    sessions: [
      {p: true, pa: false},
      {p: false, pa: true},
      {p: true, pa: false},
      {p: false, pa: false},
      {p: true, pa: true},
      {p: false, pa: false}
    ]
  }
];

const AttendanceApp = (function () {
  const STORAGE_KEY = 'attendly_new_students_v1';

  /* Helpers */
  function $(sel, ctx=document) { return ctx.querySelector(sel); }
  function $all(sel, ctx=document) { return [...ctx.querySelectorAll(sel)]; }

  /* Build a table row from a student object */
  function buildRow(student, index) {
    const tr = document.createElement('tr');
    tr.dataset.index = index;

    // Basic info
    tr.innerHTML = `
      <td class="col-id">${escapeHtml(student.id)}</td>
      <td class="col-last">${escapeHtml(student.last)}</td>
      <td class="col-first">${escapeHtml(student.first)}</td>
      <td class="col-course">${escapeHtml(student.course || '')}</td>
    `;

    // Sessions (6 sessions, each with Present checkbox and Participated checkbox)
    for (let s = 0; s < 6; s++) {
      const sess = student.sessions && student.sessions[s] ? student.sessions[s] : {p:false,pa:false};
      const pId = `p-${index}-${s}`;
      const paId = `pa-${index}-${s}`;
      const pCell = document.createElement('td');
      pCell.innerHTML = `<input type="checkbox" class="chk-present" id="${pId}" data-s="${s}" ${sess.p ? 'checked' : ''} />`;
      const paCell = document.createElement('td');
      paCell.innerHTML = `<input type="checkbox" class="chk-part" id="${paId}" data-s="${s}" ${sess.pa ? 'checked' : ''} />`;
      tr.appendChild(pCell);
      tr.appendChild(paCell);
    }

    // Absences, participation, message columns
    tr.innerHTML += `
      <td class="col-abs">0 Abs</td>
      <td class="col-par">0 Par</td>
      <td class="col-msg">-</td>
    `;

    return tr;
  }

  function escapeHtml(s) {
    if (!s) return '';
    return String(s).replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]); });
  }

  /* Calculate counts and update each row state */
  function recalcTable() {
    const tbody = $('#attendance-body');
    const rows = $all('tbody tr', tbody);

    rows.forEach((row, rowIndex) => {
      // for each of 6 sessions, present checkbox is in cell 4 + 2*s (base columns)
      const presentChecks = row.querySelectorAll('.chk-present');
      const partChecks = row.querySelectorAll('.chk-part');

      let absences = 0;
      let participation = 0;
      presentChecks.forEach((cb,i) => {
        if (!cb.checked) absences++;
      });
      partChecks.forEach(cb => { if (cb.checked) participation++; });

      // update text
      row.querySelector('.col-abs').textContent = `${absences} Abs`;
      row.querySelector('.col-par').textContent = `${participation} Par`;

      // color the row based on absences
      row.classList.remove('row-good','row-warning','row-bad');
      if (absences < 3) row.classList.add('row-good');
      else if (absences <= 4) row.classList.add('row-warning');
      else row.classList.add('row-bad');

      // Message rules (simple mapping)
      const msgCell = row.querySelector('.col-msg');
      let msg = 'Good attendance - Excellent participation';
      if (absences >= 5) {
        msg = 'Excluded - too many absences - You need to participate more';
      } else if (absences >= 3 || participation <= 2) {
        msg = 'Warning - attendance low - You need to participate more';
      }
      // Improve message when participation high
      if (absences < 3 && participation >= 4) {
        msg = 'Good attendance - Excellent participation';
      } else if (absences < 3 && participation >= 1) {
        msg = 'Good attendance - Keep participating';
      }
      msgCell.textContent = msg;
    });
  }

  /* Render table with sample data + any saved students */
  function renderTable() {
    const tbody = $('#attendance-body');
    tbody.innerHTML = '';
    let data = sampleStudents.slice();

    // load extra students from localStorage (if any)
    const saved = loadSavedStudents();
    if (saved && saved.length) {
      // saved items have only id/last/first/email; create default sessions
      saved.forEach(s => {
        const newStd = {
          id: s.id,
          last: s.last,
          first: s.first,
          course: s.course || 'Web Dev',
          sessions: [
            {p:true,pa:false},{p:true,pa:false},{p:true,pa:false},
            {p:true,pa:false},{p:true,pa:false},{p:true,pa:false}
          ]
        };
        data.push(newStd);
      });
    }

    data.forEach((student, i) => {
      const tr = buildRow(student, i);
      tbody.appendChild(tr);
    });

    // attach checkbox event listeners
    $all('.chk-present, .chk-part', tbody).forEach(el => {
      el.addEventListener('change', () => {
        recalcTable();
      });
    });

    recalcTable();
  }

  /* Local storage helpers */
  function loadSavedStudents() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : [];
    } catch(e) {
      return [];
    }
  }
  function saveStudentToStorage(student) {
    const arr = loadSavedStudents();
    arr.push(student);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(arr));
  }
  function resetSample() {
    localStorage.removeItem(STORAGE_KEY);
    renderTable();
    alert('Saved students removed; table reset to sample data.');
  }

  /* Add Student Form handling (on add-student.html) */
  function initForm() {
    const form = $('#add-student-form');
    if (!form) return;
    const idEl = $('#student-id');
    const lastEl = $('#last-name');
    const firstEl = $('#first-name');
    const emailEl = $('#email');

    function showError(elId, msg) {
      const el = document.getElementById(elId);
      if (el) el.textContent = msg || '';
    }

    form.addEventListener('submit', (ev) => {
      ev.preventDefault();

      // clear errors
      showError('err-id','');
      showError('err-last','');
      showError('err-first','');
      showError('err-email','');

      let ok = true;
      const id = idEl.value.trim();
      const last = lastEl.value.trim();
      const first = firstEl.value.trim();
      const email = emailEl.value.trim();

      if (!/^\d+$/.test(id)) {
        showError('err-id','Student ID is required and must contain only numbers.');
        ok = false;
      }
      if (!/^[A-Za-z\-'\s]+$/.test(last) || last.length === 0) {
        showError('err-last','Last name is required and must contain only letters.');
        ok = false;
      }
      if (!/^[A-Za-z\-'\s]+$/.test(first) || first.length === 0) {
        showError('err-first','First name is required and must contain only letters.');
        ok = false;
      }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showError('err-email','Please enter a valid email address.');
        ok = false;
      }

      if (!ok) return;

      // Save new student to localStorage then redirect to attendance page
      const newStudent = { id, last, first, email, course: 'Web Dev' };
      saveStudentToStorage(newStudent);

      // redirect to attendance page
      window.location.href = 'attendance.html';
    });
  }

  /* Initialize on attendance page */
  function init() {
    // If user is on attendance page, render table
    if (document.getElementById('attendance-body')) {
      renderTable();
      const resetBtn = document.getElementById('reset-sample');
      if (resetBtn) resetBtn.addEventListener('click', resetSample);
    }

    // If on add-student page, set up form (also available via initForm)
    if (document.getElementById('add-student-form')) {
      initForm();
    }
  }

  // Expose for external init calls
  return {
    init,
    initForm
  };
})();

/* Small utility: initialize form if present on DOMContentLoaded (redundant safe-call) */
document.addEventListener('DOMContentLoaded', () => {
  // Already called from each page's inline script; safe to call again.
});
