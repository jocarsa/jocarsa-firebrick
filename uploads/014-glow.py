import cv2
import numpy as np
import random
import itertools
import math

# ------------------------- Parameters -------------------------

# Screen dimensions
WIDTH, HEIGHT = 3840, 2160

# Spawn Centers
CENTER_X, CENTER_Y = WIDTH / 2, HEIGHT / 2

# Spawn Radii
PROTON_SPAWN_RADIUS = HEIGHT * 0.2    # Radius for protons
ELECTRON_SPAWN_RADIUS = HEIGHT * 0.2  # Radius for electrons (can be different if desired)
PHOTON_SPAWN_RADIUS = HEIGHT * 0.1    # Radius for photons

# Particle Colors in BGR
RED = (0, 0, 255)
GREEN = (0, 255, 0)
WHITE = (255, 255, 255)  # Photons and symbols
BLUE = (255, 0, 0)        # Blue for atoms
BLACK = (0, 0, 0)

# Particle Radii
PROTON_RADIUS = 50
ELECTRON_RADIUS = 30
PHOTON_RADIUS = 3
ATOM_RADIUS = 80  # Increased to contain protons and electrons

# Particle Masses
PROTON_MASS = 1000
ELECTRON_MASS = 1
PHOTON_MASS = 1  # Assigned mass for simulation purposes
ATOM_MASS = PROTON_MASS + ELECTRON_MASS  # Atom mass

# Initial Maximum Speed
MAX_SPEED = 5
PHOTON_SPEED = 35  # Reduced speed for better collision detection

# Photon Oscillation Parameters
PHOTON_AMPLITUDE = 5  # pixels
PHOTON_FREQUENCY = 1  # Hz

# Frame Configuration
FPS = 60
VIDEO_FILENAME = 'particle_simulation_with_bloom.mp4'  # Updated filename to reflect bloom

# Trail Opacity (0.0 to 1.0)
TRAIL_OPACITY = 0.1  # 1% opacity for smoother trails

# Association Parameters
ASSOCIATION_START_FRAME = 15 * FPS  # 5 seconds
ATOM_COLOR = BLUE

# Number of Particles
NUM_PROTONS = 50
NUM_ELECTRONS = 50
INITIAL_PHOTONS = 500

# Photon Generation Interval
PHOTON_GENERATION_INTERVAL = FPS // 2  # Every half second

# Simulation Duration
MAX_FRAMES = FPS * 60 * 5   # Run for 1 minute

# Collision Parameters
PHOTON_COLLIDE_WITH_BORDERS = False     # Set to False to disable photon border collisions
PROTON_COLLIDE_WITH_BORDERS = False     # Set to False to disable proton border collisions
ELECTRON_COLLIDE_WITH_BORDERS = False   # Set to False to disable electron border collisions
HYDROGEN_COLLIDE_WITH_BORDERS = False   # Set to False to disable hydrogen (atom) border collisions

# ------------------------- Bloom Effect Parameters -------------------------

# Bloom Effect Parameters
BLOOM_INTENSITY = 0.1   # Amount of compositing effect (additive factor). Typical range: 0.0 (no bloom) to 2.0+
BLOOM_BLUR_RADIUS = 15  # Radius for box blur (must be a positive odd integer)

# ------------------------- Scaling Parameters -------------------------

# Global Scale Factor
SCALE = 0.1  # Starts at 0.1

# Zoom Duration in Frames (15 seconds)
ZOOM_DURATION_FRAMES = 15 * FPS

# ------------------------- Helper Functions -------------------------

def ease_out_cubic(t):
    """Cubic ease-out function."""
    return 1 - pow(1 - t, 3)

def get_scaled_position(position):
    """Scale the position relative to the center based on the global SCALE."""
    scaled_x = CENTER_X + (position[0] - CENTER_X) * SCALE
    scaled_y = CENTER_Y + (position[1] - CENTER_Y) * SCALE
    return (int(scaled_x), int(scaled_y))

def get_scaled_radius(radius):
    """Scale the radius based on the global SCALE, ensuring it's at least 1."""
    return max(int(radius * SCALE), 1)

# ------------------------- Particle Classes -------------------------

# Base Particle Class
class Particle:
    def __init__(self, position, velocity, radius, color, mass):
        self.position = np.array(position, dtype=float)  # Current position
        self.velocity = np.array(velocity, dtype=float)  # Current velocity
        self.radius = radius
        self.color = color
        self.mass = mass

    def move(self, delta_time=1.0 / FPS, collide_with_borders=True):
        self.position += self.velocity * delta_time

        if collide_with_borders:
            # Bounce off walls with reflection
            for i in [0, 1]:  # 0: x-axis, 1: y-axis
                if self.position[i] <= self.radius:
                    self.position[i] = self.radius
                    self.velocity[i] *= -1
                elif self.position[i] >= (WIDTH if i == 0 else HEIGHT) - self.radius:
                    self.position[i] = (WIDTH if i == 0 else HEIGHT) - self.radius
                    self.velocity[i] *= -1

    def draw(self, frame):
        # Draw the particle with scaling
        scaled_pos = get_scaled_position(self.position)
        scaled_rad = get_scaled_radius(self.radius)
        cv2.circle(frame, scaled_pos, scaled_rad, self.color, -1, lineType=cv2.LINE_AA)

# Proton Class
class Proton(Particle):
    def __init__(self, position, velocity):
        super().__init__(position, velocity, PROTON_RADIUS, RED, PROTON_MASS)
        self.symbol = '+'  # Use '+' symbol
        self.in_atom = False  # Flag to check if part of an atom

    def draw(self, frame):
        if not self.in_atom:
            super().draw(frame)
            self.draw_centered_text(frame, self.symbol)

    def draw_centered_text(self, frame, text):
        font = cv2.FONT_HERSHEY_SIMPLEX
        base_font_scale = 0.8
        base_thickness = 2

        # Scale font size and thickness
        font_scale = max(base_font_scale * SCALE, 0.1)
        thickness = max(int(base_thickness * SCALE), 1)

        text_size, _ = cv2.getTextSize(text, font, font_scale, thickness)
        text_width, text_height = text_size

        scaled_pos = get_scaled_position(self.position)

        # Calculate bottom-left corner of text to center it
        text_x = int(scaled_pos[0] - text_width / 2)
        text_y = int(scaled_pos[1] + text_height / 2)

        # Draw text with anti-aliasing
        cv2.putText(frame, text, (text_x, text_y), font, font_scale, WHITE, thickness, cv2.LINE_AA)

# Electron Class
class Electron(Particle):
    def __init__(self, position, velocity):
        super().__init__(position, velocity, ELECTRON_RADIUS, GREEN, ELECTRON_MASS)
        self.symbol = '-'  # Use '-' symbol
        self.in_atom = False  # Flag to check if part of an atom

    def draw(self, frame):
        if not self.in_atom:
            super().draw(frame)
            self.draw_centered_text(frame, self.symbol)

    def draw_centered_text(self, frame, text):
        font = cv2.FONT_HERSHEY_SIMPLEX
        base_font_scale = 0.8
        base_thickness = 2

        # Scale font size and thickness
        font_scale = max(base_font_scale * SCALE, 0.1)
        thickness = max(int(base_thickness * SCALE), 1)

        text_size, _ = cv2.getTextSize(text, font, font_scale, thickness)
        text_width, text_height = text_size

        scaled_pos = get_scaled_position(self.position)

        # Calculate bottom-left corner of text to center it
        text_x = int(scaled_pos[0] - text_width / 2)
        text_y = int(scaled_pos[1] + text_height / 2)

        # Draw text with anti-aliasing
        cv2.putText(frame, text, (text_x, text_y), font, font_scale, WHITE, thickness, cv2.LINE_AA)

# Photon Class with Transverse Vibration
class Photon(Particle):
    def __init__(self, position, velocity, amplitude=PHOTON_AMPLITUDE, frequency=PHOTON_FREQUENCY):
        super().__init__(position, velocity, PHOTON_RADIUS, WHITE, PHOTON_MASS)  # White photons
        self.amplitude = amplitude
        self.frequency = frequency
        self.phase = random.uniform(0, 2 * np.pi)
        self.time = 0.0

        # Calculate the unit vector perpendicular for oscillation
        vx, vy = self.velocity
        norm = np.linalg.norm([vx, vy])
        if norm == 0:
            self.perp = np.array([0, 0])
        else:
            self.perp = np.array([-vy / norm, vx / norm])

    def move_photon(self, delta_time=1.0 / FPS):
        # Update base position based on velocity
        self.position += self.velocity * delta_time

        # Update time
        self.time += delta_time

        # Calculate oscillation displacement
        displacement = self.amplitude * math.sin(2 * math.pi * self.frequency * self.time + self.phase) * self.perp

        # Calculate draw position
        self.draw_position = self.position + displacement

        # Handle wall collisions based on base position
        if PHOTON_COLLIDE_WITH_BORDERS:
            collided = False
            for i in [0, 1]:
                if self.position[i] <= self.radius:
                    self.position[i] = self.radius
                    self.velocity[i] *= -1
                    collided = True
                elif self.position[i] >= (WIDTH if i == 0 else HEIGHT) - self.radius:
                    self.position[i] = (WIDTH if i == 0 else HEIGHT) - self.radius
                    self.velocity[i] *= -1
                    collided = True

            if collided:
                # Recalculate the perpendicular vector after collision
                vx, vy = self.velocity
                norm = np.linalg.norm([vx, vy])
                if norm != 0:
                    self.perp = np.array([-vy / norm, vx / norm])
                else:
                    self.perp = np.array([0, 0])
                # Reset phase to avoid synchronization
                self.phase = random.uniform(0, 2 * np.pi)

    def draw(self, frame):
        # Draw at draw_position if available, else at current position
        if hasattr(self, 'draw_position'):
            draw_pos = self.draw_position
        else:
            draw_pos = self.position

        # If photon collision with borders is disabled and photon goes out of bounds, do not draw
        if not PHOTON_COLLIDE_WITH_BORDERS:
            if (0 <= draw_pos[0] <= WIDTH) and (0 <= draw_pos[1] <= HEIGHT):
                scaled_pos = get_scaled_position(draw_pos)
                scaled_rad = get_scaled_radius(self.radius)
                cv2.circle(frame, scaled_pos, scaled_rad, self.color, -1, lineType=cv2.LINE_AA)
        else:
            scaled_pos = get_scaled_position(draw_pos)
            scaled_rad = get_scaled_radius(self.radius)
            cv2.circle(frame, scaled_pos, scaled_rad, self.color, -1, lineType=cv2.LINE_AA)

# Atom Class
class Atom(Particle):
    def __init__(self, proton, electron):
        # Calculate position and velocity as average of proton and electron
        position = (proton.position + electron.position) / 2
        velocity = (proton.velocity + electron.velocity) / 2
        super().__init__(position, velocity, ATOM_RADIUS, ATOM_COLOR, ATOM_MASS)
        self.proton = proton
        self.electron = electron
        self.in_atom = True  # Already in an atom

        # Define relative position of the electron within the atom
        # Placed at a fixed distance from the center at a random angle
        self.electron_offset_distance = ATOM_RADIUS * 0.6  # Adjust as needed
        self.electron_angle = random.uniform(0, 2 * math.pi)
        self.electron_offset = np.array([
            self.electron_offset_distance * math.cos(self.electron_angle),
            self.electron_offset_distance * math.sin(self.electron_angle)
        ])

    def move(self, delta_time=1.0 / FPS, collide_with_borders=True):
        super().move(delta_time, collide_with_borders)

    def draw(self, frame):
        # Draw the atom as a scaled solid blue circle
        scaled_pos = get_scaled_position(self.position)
        scaled_rad = get_scaled_radius(self.radius)
        cv2.circle(frame, scaled_pos, scaled_rad, self.color, -1, lineType=cv2.LINE_AA)

        # Draw the 'H' at the center of the atom
        font = cv2.FONT_HERSHEY_SIMPLEX
        base_font_scale_h = 2.0
        base_thickness_h = 3

        # Scale font size and thickness
        h_font_scale = max(base_font_scale_h * SCALE, 0.5)
        h_thickness = max(int(base_thickness_h * SCALE), 1)

        h_text = 'H'
        h_text_size, _ = cv2.getTextSize(h_text, font, h_font_scale, h_thickness)
        h_text_width, h_text_height = h_text_size

        # Calculate bottom-left corner of 'H' to center it
        h_text_x = int(scaled_pos[0] - h_text_width / 2)
        h_text_y = int(scaled_pos[1] + h_text_height / 2)
        cv2.putText(frame, h_text, (h_text_x, h_text_y), font, h_font_scale, WHITE, h_thickness, cv2.LINE_AA)

        # Draw the '+' symbol at the center of the atom
        base_font_scale_plus = 1.0
        base_thickness_plus = 2

        plus_font_scale = max(base_font_scale_plus * SCALE, 0.2)
        plus_thickness = max(int(base_thickness_plus * SCALE), 1)

        plus_text = '+'
        plus_text_size, _ = cv2.getTextSize(plus_text, font, plus_font_scale, plus_thickness)
        plus_text_width, plus_text_height = plus_text_size

        # Calculate bottom-left corner of '+' to center it
        plus_text_x = int(scaled_pos[0] - plus_text_width / 2)
        plus_text_y = int(scaled_pos[1] + plus_text_height / 2)
        cv2.putText(frame, plus_text, (plus_text_x, plus_text_y), font, plus_font_scale, WHITE, plus_thickness, cv2.LINE_AA)

        # Draw the '-' symbol at the electron's relative position
        base_font_scale_minus = 1.0
        base_thickness_minus = 2

        minus_font_scale = max(base_font_scale_minus * SCALE, 0.2)
        minus_thickness = max(int(base_thickness_minus * SCALE), 1)

        minus_text = '-'
        minus_text_size, _ = cv2.getTextSize(minus_text, font, minus_font_scale, minus_thickness)
        minus_text_width, minus_text_height = minus_text_size

        # Calculate position of '-' based on relative offset
        electron_pos = self.position + self.electron_offset
        scaled_electron_pos = get_scaled_position(electron_pos)

        minus_text_x = int(scaled_electron_pos[0] - minus_text_width / 2)
        minus_text_y = int(scaled_electron_pos[1] + minus_text_height / 2)
        cv2.putText(frame, minus_text, (minus_text_x, minus_text_y), font, minus_font_scale, WHITE, minus_thickness, cv2.LINE_AA)

# ------------------------- Initialization -------------------------

# Initialize particle lists
protons = []
electrons = []
photons = []
atoms = []

# Function to generate a random position within a circle
def random_position_within_circle(center, radius, particle_radius):
    angle = random.uniform(0, 2 * math.pi)
    r = random.uniform(0, radius - particle_radius)
    x = center[0] + r * math.cos(angle)
    y = center[1] + r * math.sin(angle)
    return [x, y]

# Function to generate a random position avoiding borders
def random_position(particle_radius):
    return [
        random.uniform(particle_radius, WIDTH - particle_radius),
        random.uniform(particle_radius, HEIGHT - particle_radius)
    ]

# Function to generate a random velocity
def random_velocity(max_speed):
    angle = random.uniform(0, 2 * math.pi)
    speed = random.uniform(0, max_speed)
    return [speed * math.cos(angle), speed * math.sin(angle)]

# Initialize protons
for _ in range(NUM_PROTONS):
    pos = random_position_within_circle([CENTER_X, CENTER_Y], PROTON_SPAWN_RADIUS, PROTON_RADIUS)
    vel = random_velocity(MAX_SPEED)
    protons.append(Proton(pos, vel))

# Initialize electrons
for _ in range(NUM_ELECTRONS):
    pos = random_position_within_circle([CENTER_X, CENTER_Y], ELECTRON_SPAWN_RADIUS, ELECTRON_RADIUS)
    vel = random_velocity(MAX_SPEED)
    electrons.append(Electron(pos, vel))

# Initialize photons
for _ in range(INITIAL_PHOTONS):
    pos = random_position_within_circle([CENTER_X, CENTER_Y], PHOTON_SPAWN_RADIUS, PHOTON_RADIUS)
    vel = random_velocity(PHOTON_SPEED)
    photons.append(Photon(pos, vel))

# Initialize Video Writer
fourcc = cv2.VideoWriter_fourcc(*'mp4v')  # You can use 'XVID' or other codecs
video_writer = cv2.VideoWriter(VIDEO_FILENAME, fourcc, FPS, (WIDTH, HEIGHT))

# Create a window to display
cv2.namedWindow('Particle Simulation with Bloom', cv2.WINDOW_NORMAL)

# Create a black image for the initial frame
frame = np.zeros((HEIGHT, WIDTH, 3), dtype=np.uint8)

# ------------------------- Collision Handling -------------------------

# Function to handle elastic collision between two particles
def handle_collision(p1, p2):
    # Vector between centers
    delta_pos = p1.position - p2.position
    distance = np.linalg.norm(delta_pos)
    if distance == 0:
        # Prevent division by zero by assigning a small random displacement
        delta_pos = np.array([random.uniform(-0.1, 0.1), random.uniform(-0.1, 0.1)])
        distance = np.linalg.norm(delta_pos)

    # Check if particles are overlapping
    if distance < (p1.radius + p2.radius):
        # Normalize the vector
        normal = delta_pos / distance
        # Relative velocity
        relative_velocity = p1.velocity - p2.velocity
        # Velocity along the normal
        velocity_along_normal = np.dot(relative_velocity, normal)
        if velocity_along_normal > 0:
            return  # They are moving away

        # Calculate impulse
        impulse = (2 * velocity_along_normal) / (p1.mass + p2.mass)
        # Update velocities based on mass
        p1.velocity -= (impulse * p2.mass) * normal
        p2.velocity += (impulse * p1.mass) * normal

        # Separate overlapping particles proportionally to their masses
        overlap = (p1.radius + p2.radius) - distance
        separation = normal * (overlap / 2)
        p1.position += separation
        p2.position -= separation

# Function to handle collision between proton and electron to form an atom
def handle_proton_electron_collision(proton, electron):
    # Vector between centers
    delta_pos = proton.position - electron.position
    distance = np.linalg.norm(delta_pos)
    if distance == 0:
        # Prevent division by zero by assigning a small random displacement
        delta_pos = np.array([random.uniform(-0.1, 0.1), random.uniform(-0.1, 0.1)])
        distance = np.linalg.norm(delta_pos)

    # Check if particles are overlapping
    if distance < (proton.radius + electron.radius):
        # Normalize the vector
        normal = delta_pos / distance
        # Relative velocity
        relative_velocity = proton.velocity - electron.velocity
        # Velocity along the normal
        velocity_along_normal = np.dot(relative_velocity, normal)
        if velocity_along_normal > 0:
            return  # They are moving away

        # Create an atom
        atom = Atom(proton, electron)
        atoms.append(atom)

        # Remove proton and electron from free lists
        if proton in protons:
            protons.remove(proton)
        if electron in electrons:
            electrons.remove(electron)

# ------------------------- Bloom Effect Function -------------------------

def apply_bloom(frame, intensity=BLOOM_INTENSITY, blur_radius=BLOOM_BLUR_RADIUS):
    """
    Apply a bloom effect to the given frame.

    Parameters:
        frame (numpy.ndarray): The original frame in BGR format.
        intensity (float): The amount of compositing effect. Typical range: 0.0 (no bloom) to 2.0+.
        blur_radius (int): The radius for the box blur (must be a positive odd integer).

    Returns:
        numpy.ndarray: The frame with the bloom effect applied.
    """
    # Ensure blur_radius is an odd integer
    if blur_radius % 2 == 0:
        blur_radius += 1

    # Duplicate the main frame
    blurred_frame = frame.copy()

    # Apply a box blur to the duplicated frame
    blurred_frame = cv2.blur(blurred_frame, (blur_radius, blur_radius))

    # Scale the blurred frame by intensity
    blurred_frame = cv2.convertScaleAbs(blurred_frame, alpha=intensity, beta=0)

    # Composite the blurred frame over the original frame using ADD operation
    bloom_frame = cv2.add(frame, blurred_frame)

    return bloom_frame

# ------------------------- Simulation Loop -------------------------

frame_count = 0

while frame_count < MAX_FRAMES:
    # Update the global SCALE based on frame_count for zoom effect with easing
    if frame_count < ZOOM_DURATION_FRAMES:
        t = frame_count / ZOOM_DURATION_FRAMES  # Normalized time [0,1]
        eased_t = ease_out_cubic(t)
        SCALE = 0.1 + 0.9 * eased_t
    else:
        SCALE = 1.0

    # Overlay a semi-transparent black rectangle to fade previous frames (create trails)
    overlay = frame.copy()
    cv2.rectangle(overlay, (0, 0), (WIDTH, HEIGHT), BLACK, -1)
    cv2.addWeighted(overlay, TRAIL_OPACITY, frame, 1 - TRAIL_OPACITY, 0, frame)

    # Move all particles
    for particle in protons + electrons + photons + atoms:
        if isinstance(particle, Photon):
            particle.move_photon()
        elif isinstance(particle, Atom):
            # Pass the HYDROGEN_COLLIDE_WITH_BORDERS parameter to control border collision
            particle.move(delta_time=1.0 / FPS, collide_with_borders=HYDROGEN_COLLIDE_WITH_BORDERS)
        elif isinstance(particle, Proton):
            particle.move(delta_time=1.0 / FPS, collide_with_borders=PROTON_COLLIDE_WITH_BORDERS)
        elif isinstance(particle, Electron):
            particle.move(delta_time=1.0 / FPS, collide_with_borders=ELECTRON_COLLIDE_WITH_BORDERS)

    # Handle proton-electron collisions to form atoms after association start frame
    if frame_count >= ASSOCIATION_START_FRAME:
        # Use itertools.product to get all possible proton-electron pairs
        for proton, electron in itertools.product(protons.copy(), electrons.copy()):
            handle_proton_electron_collision(proton, electron)

    # Handle collisions between all free particles
    # Combine protons, electrons, photons, and atoms into a single list
    free_particles = protons + electrons + photons + atoms
    for p1, p2 in itertools.combinations(free_particles, 2):
        # Avoid collisions with the same atom or with itself
        if p1 is p2:
            continue
        # Optionally, you can add more conditions to prevent atom-atom collisions
        handle_collision(p1, p2)

    # Optionally, generate new photons to keep the simulation dynamic
    if frame_count % PHOTON_GENERATION_INTERVAL == 0:  # Every half second
        pos = random_position_within_circle([CENTER_X, CENTER_Y], PHOTON_SPAWN_RADIUS, PHOTON_RADIUS)
        vel = random_velocity(PHOTON_SPEED)
        photons.append(Photon(pos, vel))

    # Draw all particles
    for proton in protons:
        proton.draw(frame)
    for electron in electrons:
        electron.draw(frame)
    for photon in photons:
        photon.draw(frame)
    for atom in atoms:
        atom.draw(frame)

    # ------------------------- Apply Bloom Effect -------------------------
    # Apply the bloom effect to the current frame using the defined parameters
    frame_with_bloom = apply_bloom(frame, intensity=BLOOM_INTENSITY, blur_radius=BLOOM_BLUR_RADIUS)
    # Replace the original frame with the bloomed frame for display and saving
    display_frame = frame_with_bloom.copy()
    frame = frame_with_bloom.copy()
    # ---------------------------------------------------------------------

    # Write the frame to the video
    video_writer.write(frame)

    # Display the frame
    cv2.imshow('Particle Simulation with Bloom', display_frame)

    # Exit if 'q' is pressed
    if cv2.waitKey(1) & 0xFF == ord('q'):
        break

    frame_count += 1

# ------------------------- Cleanup -------------------------

# Release video writer and destroy all OpenCV windows
video_writer.release()
cv2.destroyAllWindows()

print(f"Simulation completed. Video saved as {VIDEO_FILENAME}")
