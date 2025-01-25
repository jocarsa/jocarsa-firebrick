import cv2
import numpy as np
import random
import itertools
import math

# ------------------------- Parameters -------------------------

# Screen dimensions
WIDTH, HEIGHT = 1920, 1080

# Spawn Centers
CENTER_X, CENTER_Y = WIDTH / 2, HEIGHT / 2

# Spawn Radii
PROTON_SPAWN_RADIUS = HEIGHT * 0.3    # Radius for protons
ELECTRON_SPAWN_RADIUS = HEIGHT * 0.3  # Radius for electrons (can be different if desired)
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
FPS = 30
VIDEO_FILENAME = 'particle_simulation.mp4'

# Trail Opacity (0.0 to 1.0)
TRAIL_OPACITY = 0.01  # 1% opacity for smoother trails

# Association Parameters
ASSOCIATION_START_FRAME = 15 * FPS  # 5 seconds
ATOM_COLOR = BLUE

# Number of Particles
NUM_PROTONS = 10
NUM_ELECTRONS = 10
INITIAL_PHOTONS = 100

# Photon Generation Interval
PHOTON_GENERATION_INTERVAL = FPS // 2  # Every half second

# Simulation Duration
MAX_FRAMES = FPS * 60 * 1   # Run for 5 minutes

# Collision Parameters
PHOTON_COLLIDE_WITH_BORDERS = False     # Set to False to disable photon border collisions
PROTON_COLLIDE_WITH_BORDERS = False     # Set to False to disable proton border collisions
ELECTRON_COLLIDE_WITH_BORDERS = False   # Set to False to disable electron border collisions

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
        # Draw the particle with anti-aliasing
        cv2.circle(frame, self.get_int_position(), self.radius, self.color, -1, lineType=cv2.LINE_AA)

    def get_int_position(self):
        return (int(self.position[0]), int(self.position[1]))

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
        font_scale = 0.8
        thickness = 2
        text_size, _ = cv2.getTextSize(text, font, font_scale, thickness)
        text_width, text_height = text_size

        # Calculate bottom-left corner of text to center it
        text_x = int(self.position[0] - text_width / 2)
        text_y = int(self.position[1] + text_height / 2)

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
        font_scale = 0.8
        thickness = 2
        text_size, _ = cv2.getTextSize(text, font, font_scale, thickness)
        text_width, text_height = text_size

        # Calculate bottom-left corner of text to center it
        text_x = int(self.position[0] - text_width / 2)
        text_y = int(self.position[1] + text_height / 2)

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
                cv2.circle(frame, (int(draw_pos[0]), int(draw_pos[1])), self.radius, self.color, -1, lineType=cv2.LINE_AA)
        else:
            cv2.circle(frame, (int(draw_pos[0]), int(draw_pos[1])), self.radius, self.color, -1, lineType=cv2.LINE_AA)

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
        self.electron_angle = random.uniform(0, 2 * np.pi)
        self.electron_offset = np.array([
            self.electron_offset_distance * math.cos(self.electron_angle),
            self.electron_offset_distance * math.sin(self.electron_angle)
        ])

    def draw(self, frame):
        # Draw the atom as a solid blue circle
        cv2.circle(frame, self.get_int_position(), self.radius, self.color, -1, lineType=cv2.LINE_AA)

        # Draw the 'H' at the center of the atom
        font = cv2.FONT_HERSHEY_SIMPLEX
        h_font_scale = 2.0  # Large font size for 'H'
        h_thickness = 3
        h_text = 'H'
        h_text_size, _ = cv2.getTextSize(h_text, font, h_font_scale, h_thickness)
        h_text_width, h_text_height = h_text_size

        # Calculate bottom-left corner of 'H' to center it
        h_text_x = int(self.position[0] - h_text_width / 2)
        h_text_y = int(self.position[1] + h_text_height / 2)
        cv2.putText(frame, h_text, (h_text_x, h_text_y), font, h_font_scale, WHITE, h_thickness, cv2.LINE_AA)

        # Draw the '+' symbol at the center of the atom
        plus_text = '+'
        plus_font_scale = 1.0  # Font size for '+'
        plus_thickness = 2
        plus_text_size, _ = cv2.getTextSize(plus_text, font, plus_font_scale, plus_thickness)
        plus_text_width, plus_text_height = plus_text_size

        # Calculate bottom-left corner of '+' to center it
        plus_text_x = int(self.position[0] - plus_text_width / 2)
        plus_text_y = int(self.position[1] + plus_text_height / 2)
        cv2.putText(frame, plus_text, (plus_text_x, plus_text_y), font, plus_font_scale, WHITE, plus_thickness, cv2.LINE_AA)

        # Draw the '-' symbol at the electron's relative position
        minus_text = '-'
        minus_font_scale = 1.0  # Font size for '-'
        minus_thickness = 2
        minus_text_size, _ = cv2.getTextSize(minus_text, font, minus_font_scale, minus_thickness)
        minus_text_width, minus_text_height = minus_text_size

        # Calculate position of '-' based on relative offset
        minus_text_x = int(self.position[0] + self.electron_offset[0] - minus_text_width / 2)
        minus_text_y = int(self.position[1] + self.electron_offset[1] + minus_text_height / 2)
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
cv2.namedWindow('Particle Simulation', cv2.WINDOW_NORMAL)

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

# ------------------------- Simulation Loop -------------------------

frame_count = 0

while frame_count < MAX_FRAMES:
    # Overlay a semi-transparent black rectangle to fade previous frames (create trails)
    overlay = frame.copy()
    cv2.rectangle(overlay, (0, 0), (WIDTH, HEIGHT), BLACK, -1)
    cv2.addWeighted(overlay, TRAIL_OPACITY, frame, 1 - TRAIL_OPACITY, 0, frame)

    # Move all particles
    for particle in protons + electrons + photons + atoms:
        if isinstance(particle, Photon):
            particle.move_photon()
        elif isinstance(particle, Atom):
            particle.move()
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

    # Write the frame to the video
    video_writer.write(frame)

    # Display the frame
    cv2.imshow('Particle Simulation', frame)

    # Exit if 'q' is pressed
    if cv2.waitKey(1) & 0xFF == ord('q'):
        break

    frame_count += 1

# ------------------------- Cleanup -------------------------

# Release video writer and destroy all OpenCV windows
video_writer.release()
cv2.destroyAllWindows()

print(f"Simulation completed. Video saved as {VIDEO_FILENAME}")
