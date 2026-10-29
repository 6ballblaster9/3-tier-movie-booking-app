const fallbackMovies = [
  { id: 1, title: 'The Last Horizon', art: 'THE LAST<br>HORIZON', meta: 'IMAX with Laser · English', genre: 'Sci-fi', duration: '2h 28m', rating: 8.7, price: 420 },
  { id: 2, title: 'Golden Hour', art: 'GOLDEN<br>HOUR', meta: 'Dolby Atmos · Hindi', genre: 'Drama', duration: '2h 06m', rating: 8.2, price: 330 },
  { id: 3, title: 'Blue Current', art: 'BLUE<br>CURRENT', meta: '3D · English', genre: 'Adventure', duration: '1h 54m', rating: 8.5, price: 380 },
  { id: 4, title: 'Midnight Run', art: 'MIDNIGHT<br>RUN', meta: 'Dolby Atmos · Hindi', genre: 'Thriller', duration: '2h 13m', rating: 7.9, price: 350 }
];

const state = {
  movies: [...fallbackMovies],
  selectedMovie: null,
  selectedDate: null,
  selectedShowtime: null,
  selectedSeats: [],
  showtimes: [],
  seatAvailability: []
};

const cards = document.querySelector('#movieCards');
const datesEl = document.querySelector('#dates');
const showsEl = document.querySelector('#showtimes');
const details = document.querySelector('#bookingDetails');
const summary = document.querySelector('#summary');
const message = document.querySelector('#flowMessage');
const checkoutPanel = document.querySelector('#checkoutPanel');
const confirmationPanel = document.querySelector('#confirmationPanel');
const checkoutForm = document.querySelector('#checkoutForm');
const checkoutError = document.querySelector('#checkoutError');

bindEvents();
loadMovies();

function bindEvents() {
  document.querySelector('#changeMovie').onclick = () => {
    document.querySelector('.movie-picker').scrollIntoView({ behavior: 'smooth', block: 'center' });
  };

  document.querySelector('#continueBtn').onclick = () => {
    if (!state.selectedMovie || !state.selectedShowtime || !state.selectedSeats.length) {
      return;
    }

    checkoutPanel.hidden = false;
    confirmationPanel.hidden = true;
    checkoutError.hidden = true;
    checkoutForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  document.querySelector('#backToSeatsBtn').onclick = () => {
    checkoutPanel.hidden = true;
    checkoutError.hidden = true;
    document.querySelector('#seatmap').scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  document.querySelector('#bookAnotherBtn').onclick = () => {
    resetBookingFlow();
    document.querySelector('.movie-picker').scrollIntoView({ behavior: 'smooth', block: 'center' });
  };

  checkoutForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    await createBooking();
  });
}

async function loadMovies() {
  try {
    setFlowMessage('Loading movies...');
    const response = await fetch('api/movies.php');
    const payload = await response.json();

    if (response.ok && payload.success && Array.isArray(payload.data) && payload.data.length) {
      state.movies = payload.data;
    } else {
      state.movies = [...fallbackMovies];
      setFlowMessage('Showing the original movie lineup.', false, true);
    }
  } catch (error) {
    state.movies = [...fallbackMovies];
    setFlowMessage('Showing the original movie lineup.', false, true);
  }

  cards.innerHTML = '';

  if (!state.movies.length) {
    setFlowMessage('No movies are available right now.', true);
    return;
  }

  details.hidden = true;
  summary.hidden = true;
  message.hidden = false;
  message.textContent = 'Select a movie above to view upcoming shows and available seats.';
  message.style.color = '#4d586d';

  state.movies.forEach((movie, index) => {
    const card = document.createElement('button');
    card.type = 'button';
    card.className = 'movie-card';
    const movieGenre = movie.genre || 'Movie';
    const movieRating = movie.rating ?? '8.0';
    card.innerHTML = `<div class="card-art">${movie.art}</div><div class="card-info"><strong>${movie.title}</strong><span>${movieGenre} · ★ ${movieRating}</span></div>`;
    card.onclick = () => selectMovie(index);
    cards.append(card);
  });
}

async function selectMovie(index) {
  const movie = state.movies[index];
  if (!movie) return;

  state.selectedMovie = movie;
  state.selectedDate = null;
  state.selectedShowtime = null;
  state.selectedSeats = [];
  state.showtimes = [];
  state.seatAvailability = [];
  checkoutPanel.hidden = true;
  confirmationPanel.hidden = true;

  document.querySelectorAll('.movie-card').forEach((card, i) => {
    card.classList.toggle('selected', i === index);
  });

  const miniPoster = document.querySelector('#miniPoster');
  const selectedMovieName = document.querySelector('#selectedMovieName');
  const selectedMovieMeta = document.querySelector('#selectedMovieMeta');
  const selectedMovieRating = document.querySelector('#selectedMovieRating');

  miniPoster.textContent = movie.title.toUpperCase();
  selectedMovieName.textContent = movie.title;
  selectedMovieMeta.textContent = movie.meta;
  selectedMovieRating.innerHTML = `★ ${movie.rating} <span>IMDb</span>`;

  details.hidden = false;
  summary.hidden = false;
  message.hidden = true;
  document.querySelector('#seatStep').classList.add('current');

  renderDates();
  updateSummary();
  details.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function renderDates() {
  datesEl.innerHTML = '';
  const start = new Date();
  start.setHours(0, 0, 0, 0);

  for (let offset = 0; offset < 7; offset += 1) {
    const date = new Date(start);
    date.setDate(start.getDate() + offset);
    const key = toDateInputValue(date);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = `date${offset === 0 ? ' active' : ''}`;
    button.dataset.dateKey = key;
    button.innerHTML = `${date.toLocaleDateString('en-IN', { weekday: 'short' }).toUpperCase()}<strong>${date.getDate()}</strong>${date.toLocaleDateString('en-IN', { month: 'short' }).toUpperCase()}`;
    button.onclick = async () => {
      document.querySelectorAll('.date').forEach((dateBtn) => dateBtn.classList.remove('active'));
      button.classList.add('active');
      state.selectedDate = date;
      state.selectedShowtime = null;
      state.selectedSeats = [];
      await loadShowtimes();
    };

    if (offset === 0 && !state.selectedDate) {
      state.selectedDate = date;
    }

    datesEl.append(button);
  }

  const activeDate = document.querySelector(`.date[data-date-key="${toDateInputValue(state.selectedDate || new Date())}"]`);
  if (activeDate) {
    document.querySelectorAll('.date').forEach((dateBtn) => dateBtn.classList.remove('active'));
    activeDate.classList.add('active');
  }

  if (state.selectedDate) {
    loadShowtimes();
  }
}

async function loadShowtimes() {
  if (!state.selectedMovie || !state.selectedDate) return;

  const dateValue = toDateInputValue(state.selectedDate);

  try {
    setFlowMessage('Loading showtimes...');
    const response = await fetch(`api/shows.php?movie_id=${state.selectedMovie.id}&date=${dateValue}`);
    const payload = await response.json();

    if (!response.ok || !payload.success) {
      throw new Error(payload.error?.message || 'Unable to load showtimes.');
    }

    state.showtimes = payload.data || [];
    renderShowtimes();
    updateSummary();
  } catch (error) {
    setFlowMessage(error.message || 'Unable to load showtimes.', true);
    state.showtimes = [];
    renderShowtimes();
  }
}

function renderShowtimes() {
  showsEl.innerHTML = '';
  const noShows = document.querySelector('#noShows');

  if (!state.showtimes.length) {
    noShows.hidden = false;
    state.selectedShowtime = null;
    renderSeatMap();
    return;
  }

  noShows.hidden = true;

  state.showtimes.forEach((show) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = show.formatted_time;
    button.classList.toggle('selected', state.selectedShowtime && state.selectedShowtime.id === show.id);

    button.onclick = async () => {
      state.selectedShowtime = show;
      state.selectedSeats = [];
      renderShowtimes();
      updateSummary();
      await loadSeats(show.id);
    };

    showsEl.append(button);
  });

  if (!state.selectedShowtime && state.showtimes.length) {
    state.selectedShowtime = state.showtimes[0];
    renderShowtimes();
    loadSeats(state.showtimes[0].id);
  }
}

async function loadSeats(showtimeId) {
  if (!showtimeId) return;

  try {
    const response = await fetch(`api/seats.php?showtime_id=${showtimeId}`);
    const payload = await response.json();

    if (!response.ok || !payload.success) {
      throw new Error(payload.error?.message || 'Unable to load seat availability.');
    }

    state.seatAvailability = payload.data || [];
    renderSeatMap();
    updateSummary();
  } catch (error) {
    setFlowMessage(error.message || 'Unable to load seat availability.', true);
    state.seatAvailability = [];
    renderSeatMap();
  }
}

function renderSeatMap() {
  const map = document.querySelector('#seatmap');
  map.innerHTML = '';

  const seatLookup = new Map((state.seatAvailability || []).map((seat) => [seat.seat, seat.available]));

  'ABCDEFG'.split('').forEach((row) => {
    const rowEl = document.createElement('div');
    rowEl.className = 'seat-row';
    rowEl.innerHTML = `<span class="row-label">${row}</span>`;

    for (let number = 1; number <= 10; number += 1) {
      const seatCode = `${row}${number}`;
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'seat-btn';
      button.dataset.seat = seatCode;
      button.setAttribute('aria-label', `Seat ${seatCode}`);

      const isAvailable = seatLookup.has(seatCode) ? seatLookup.get(seatCode) : true;
      const isTaken = !state.selectedShowtime || !isAvailable;
      const isSelected = state.selectedSeats.includes(seatCode);

      if (isTaken) {
        button.classList.add('occupied');
        button.disabled = true;
      } else if (isSelected) {
        button.classList.add('selected');
      }

      if (!isTaken && !isSelected) {
        button.onclick = () => toggleSeat(seatCode);
      }

      rowEl.append(button);
    }

    rowEl.insertAdjacentHTML('beforeend', '<span class="row-label">' + row + '</span>');
    map.append(rowEl);
  });
}

function toggleSeat(seatCode) {
  const current = [...state.selectedSeats];

  if (current.includes(seatCode)) {
    state.selectedSeats = current.filter((seat) => seat !== seatCode);
  } else {
    if (current.length >= 6) {
      setFlowMessage('You can select up to 6 seats.', true);
      return;
    }
    state.selectedSeats = [...current, seatCode];
  }

  setFlowMessage('Select a movie above to view upcoming shows and available seats.', false, true);
  renderSeatMap();
  updateSummary();
}

function updateSummary() {
  const seatCount = state.selectedSeats.length;
  const dateText = state.selectedDate ? state.selectedDate.toLocaleDateString('en-IN', { weekday: 'short', day: 'numeric', month: 'short' }) : 'Select a date';
  const showtimeText = state.selectedShowtime ? state.selectedShowtime.formatted_time : 'Select a showtime';

  document.querySelector('#summaryInfo').textContent = state.selectedShowtime ? `${dateText} · ${showtimeText}` : `${dateText} · Select a showtime`;
  document.querySelector('#summarySeats').textContent = seatCount && state.selectedMovie ? `${state.selectedMovie.title} · ${state.selectedSeats.join(', ')}` : 'Select your seats';

  const totalPrice = seatCount * (Number(state.selectedMovie?.price || 0));
  document.querySelector('#total').textContent = `₹${totalPrice}`;
  document.querySelector('#continueBtn').disabled = !state.selectedMovie || !state.selectedShowtime || seatCount === 0;
}

function setFlowMessage(messageText, isError = false, clear = false) {
  if (clear) {
    message.hidden = true;
    return;
  }

  message.textContent = messageText;
  message.style.color = isError ? '#b93f32' : '#4d586d';
  message.hidden = false;
}

function toDateInputValue(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

async function createBooking() {
  const name = document.querySelector('#customerName').value.trim();
  const email = document.querySelector('#customerEmail').value.trim();

  if (!state.selectedMovie || !state.selectedShowtime) {
    showCheckoutError('Please choose a movie and showtime first.');
    return;
  }

  if (name.length < 2) {
    showCheckoutError('Full name must be at least 2 characters long.');
    return;
  }

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailPattern.test(email)) {
    showCheckoutError('Please enter a valid email address.');
    return;
  }

  if (!state.selectedSeats.length) {
    showCheckoutError('Please select at least one seat.');
    return;
  }

  const payload = {
    showtime_id: state.selectedShowtime.id,
    customer_name: name,
    customer_email: email,
    seats: state.selectedSeats
  };

  const submitBtn = document.querySelector('#confirmBookingBtn');
  submitBtn.disabled = true;
  submitBtn.textContent = 'Booking...';

  try {
    const response = await fetch('api/bookings.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const result = await response.json().catch(() => ({ success: false, error: { message: 'Booking failed. Please try again.' } }));

    if (!response.ok || !result.success) {
      const errorMessage = result.error?.message || 'Booking failed. Please try again.';
      if (response.status === 409) {
        showCheckoutError('This seat has already been booked.');
        state.selectedSeats = [];
        await loadSeats(state.selectedShowtime.id);
        checkoutPanel.hidden = false;
      } else {
        showCheckoutError(errorMessage);
      }
      return;
    }

    const data = result.data;
    renderConfirmation(data);
    checkoutPanel.hidden = true;
    confirmationPanel.hidden = false;
    state.selectedSeats = [];
    state.selectedShowtime = null;
    state.showtimes = [];
    renderShowtimes();
    updateSummary();
  } catch (error) {
    showCheckoutError('Server is temporarily unavailable. Please try again.');
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Confirm booking';
  }
}

function showCheckoutError(messageText) {
  checkoutError.textContent = messageText;
  checkoutError.hidden = false;
}

function renderConfirmation(data) {
  document.querySelector('#confirmationReference').textContent = data.booking_reference;
  document.querySelector('#confirmationMovie').textContent = data.movie;
  document.querySelector('#confirmationDate').textContent = state.selectedDate ? state.selectedDate.toLocaleDateString('en-IN', { weekday: 'short', day: 'numeric', month: 'short' }) : '-';
  document.querySelector('#confirmationTime').textContent = data.showtime;
  document.querySelector('#confirmationSeats').textContent = data.seats.join(', ');
  document.querySelector('#confirmationTotal').textContent = `₹${Number(data.total_amount).toFixed(0)}`;
}

function resetBookingFlow() {
  state.selectedMovie = null;
  state.selectedDate = null;
  state.selectedShowtime = null;
  state.selectedSeats = [];
  state.showtimes = [];
  state.seatAvailability = [];

  checkoutPanel.hidden = true;
  confirmationPanel.hidden = true;
  checkoutForm.reset();
  checkoutError.hidden = true;

  document.querySelectorAll('.movie-card').forEach((card) => card.classList.remove('selected'));
  document.querySelector('#bookingDetails').hidden = true;
  document.querySelector('#summary').hidden = true;
  message.hidden = false;
  message.textContent = 'Select a movie above to view upcoming shows and available seats.';
  message.style.color = '#4d586d';
}

window.addEventListener('DOMContentLoaded', () => {
  document.querySelector('#customerName').value = '';
  document.querySelector('#customerEmail').value = '';
});
