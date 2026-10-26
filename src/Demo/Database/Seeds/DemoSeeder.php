<?php

namespace Kpcrudy\Demo\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Fills the demo tables with deterministic sample data and generates
 * placeholder avatars and posters with GD (skipped if GD is not available).
 *
 * Run by "php spark kpcrudy:demo install" (see Kpcrudy\Demo\Installer).
 */
class DemoSeeder extends Seeder
{
    private const AVATAR_DIR = FCPATH . 'uploads/kpcrudy-demo/avatars';
    private const POSTER_DIR = FCPATH . 'uploads/kpcrudy-demo/posters';

    private const CATEGORIES = [
        ['Music', '#6f42c1', 'Concerts, open rehearsals and jam sessions.'],
        ['Theatre', '#d63384', 'Performing arts: plays, improv and workshops.'],
        ['Sports', '#198754', 'Sports and outdoor activities.'],
        ['Technology', '#0d6efd', 'Talks and workshops on software and hardware.'],
        ['Food', '#fd7e14', 'Tastings, cooking classes and community meals.'],
        ['Training', '#20c997', 'Short courses on personal and professional skills.'],
    ];

    private const EVENTS = [
        'Music'      => ['Jazz in the courtyard', 'Spring jam session', 'Sea shanty night', 'Choir open rehearsal', 'Singer-songwriter night', 'Young bands festival'],
        'Theatre'    => ['Live improv show', 'Dramatized reading', 'Clown workshop', 'Premiere: The haunted house', 'Puppet theatre', 'Thursday stand-up'],
        'Sports'     => ['Community 5K run', '3x3 basketball tournament', 'Mountain bike ride', 'Yoga in the park', 'Charity football match', 'Night hike'],
        'Technology' => ['Introduction to CodeIgniter 4', 'Arduino workshop', 'Open data hackathon', 'Talk: AI in the classroom', 'Git for beginners', 'Build your home server'],
        'Food'       => ['Local wine tasting', 'Vegetarian cooking class', 'Community barbecue', 'Tapas tour', 'Sourdough bread', 'Dessert cooking show'],
        'Training'   => ['First aid', 'Public speaking', 'Smartphone photography', 'Advanced spreadsheets', 'CV workshop', 'Graphic design basics'],
    ];

    private const LOCATIONS = ['Main hall', 'Courtyard', 'Room 2.4', 'Library', 'Sports hall', 'Community centre', 'Town square', 'Online'];

    private const FIRST_NAMES = ['John', 'Emma', 'Liam', 'Olivia', 'Noah', 'Sophia', 'James', 'Mia', 'Lucas', 'Grace', 'Henry', 'Chloe', 'Daniel', 'Ruby', 'Jack', 'Zoe', 'Leo', 'Ella', 'Oscar', 'Lily', 'Ethan', 'Ivy', 'Max'];
    private const LAST_NAMES  = ['Smith', 'Johnson', 'Brown', 'Taylor', 'Wilson', 'Davies', 'Evans', 'Thomas', 'Roberts', 'Walker', 'Wright', 'Hall', 'Green', 'Wood', 'Clarke', 'Hughes', 'Lewis', 'Young', 'King', 'Baker', 'Hill', 'Moore', 'Cooper'];

    private const BIOS = [
        'I love outdoor concerts and hands-on workshops.',
        'Secondary school teacher, always looking for new ideas for the classroom.',
        'Trail runner and amateur photographer.',
        'Web development student.',
        null,
    ];

    public function run(): void
    {
        mt_srand(2026);

        $this->cleanUploads();

        $now = date('Y-m-d H:i:s');

        $categories = $this->seedCategories($now);
        $users      = $this->seedUsers($now);
        $this->seedInterests($users, $categories);
        $events = $this->seedEvents($now, $categories, $users[0]['id']);
        $this->seedAttendances($now, $events, $users);
    }

    /**
     * @return array<string, array{id: int, color: string}>
     */
    private function seedCategories(string $now): array
    {
        $result = [];

        foreach (self::CATEGORIES as [$name, $color, $description]) {
            $this->db->table('kpdemo_categories')->insert([
                'name'        => $name,
                'color'       => $color,
                'description' => $description,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $result[$name] = ['id' => (int) $this->db->insertID(), 'color' => $color];
        }

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function seedUsers(string $now): array
    {
        $adminHash = password_hash('admin1234', PASSWORD_DEFAULT);
        $userHash  = password_hash('user1234', PASSWORD_DEFAULT);

        $people = [
            ['admin', 'Admin', 'Demo', 'admin', $adminHash],
            ['alice', 'Alice', 'Martin', 'user', $userHash],
        ];

        foreach (self::FIRST_NAMES as $i => $first) {
            $last     = self::LAST_NAMES[$i];
            $people[] = [$this->slug($first) . '.' . $this->slug($last), $first, $last, $i === 0 ? 'admin' : 'user', $userHash];
        }

        $users = [];

        foreach ($people as $i => [$username, $first, $last, $role, $hash]) {
            $row = [
                'username'   => $username,
                'password'   => $hash,
                'first_name' => $first,
                'last_name'  => $last,
                'email'      => $username . '@example.com',
                // A few users without avatar, to show the empty value
                'avatar'     => $i % 5 === 4 ? null : $this->avatar($first, $last, $i),
                'birthdate'  => sprintf('%d-%02d-%02d', mt_rand(1965, 2006), mt_rand(1, 12), mt_rand(1, 28)),
                'bio'        => self::BIOS[$i % count(self::BIOS)],
                'role'       => $role,
                // Two inactive users and two in the trash
                'active'     => in_array($i, [7, 15], true) ? 0 : 1,
                'created_at' => date('Y-m-d H:i:s', strtotime("-{$i} days")),
                'updated_at' => $now,
                'deleted_at' => in_array($i, [11, 20], true) ? $now : null,
            ];

            $this->db->table('kpdemo_users')->insert($row);
            $row['id'] = (int) $this->db->insertID();
            $users[]   = $row;
        }

        return $users;
    }

    /**
     * @param list<array<string, mixed>>               $users
     * @param array<string, array{id: int, color: string}> $categories
     */
    private function seedInterests(array $users, array $categories): void
    {
        $ids  = array_column($categories, 'id');
        $rows = [];

        foreach ($users as $user) {
            $picked = (array) array_rand(array_flip($ids), mt_rand(1, 3));

            foreach ($picked as $categoryId) {
                $rows[] = ['user_id' => $user['id'], 'category_id' => $categoryId];
            }
        }

        $this->db->table('kpdemo_user_interests')->insertBatch($rows);
    }

    /**
     * @param array<string, array{id: int, color: string}> $categories
     *
     * @return list<array<string, mixed>>
     */
    private function seedEvents(string $now, array $categories, int $adminId): array
    {
        $events = [];
        $i      = 0;

        foreach (self::EVENTS as $category => $titles) {
            foreach ($titles as $title) {
                // Spread the events from ~50 days ago to ~90 days ahead
                $offset   = -50 + (($i * 7) % 36) * 4;
                $hour     = [10, 12, 17, 18, 19][$i % 5];
                $startsAt = date('Y-m-d H:i:00', strtotime("today {$offset} days {$hour}:00"));
                $status   = match (true) {
                    $i % 9 === 4  => 'draft',
                    $i % 11 === 7 => 'cancelled',
                    default       => 'published',
                };

                $row = [
                    'category_id' => $categories[$category]['id'],
                    'title'       => $title,
                    'summary'     => $category . ' activity open to the whole community.',
                    'description' => "{$title}.\n\nA great chance to have fun and learn something new. "
                        . "Registration is required because places are limited.\n\nSee you there!",
                    'starts_at'   => $startsAt,
                    'ends_at'     => $i % 4 === 3 ? null : date('Y-m-d H:i:00', strtotime($startsAt . ' +2 hours')),
                    'location'    => self::LOCATIONS[$i % count(self::LOCATIONS)],
                    'capacity'    => [15, 20, 25, 30, 40, null][$i % 6],
                    'price'       => [0, 0, 5, 10, 12.5, 0, 3][$i % 7],
                    // Same layout as the CRUD's 'subdir' option: posters/{year}/{month}/file.png
                    'poster'      => $i % 6 === 5 ? null : $this->poster($title, $category, $categories[$category]['color'], $i, date('Y/m', strtotime($startsAt))),
                    'status'      => $status,
                    'created_by'  => $adminId,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                    // Three events in the trash
                    'deleted_at'  => in_array($i, [5, 17, 29], true) ? $now : null,
                ];

                $this->db->table('kpdemo_events')->insert($row);
                $row['id'] = (int) $this->db->insertID();
                $events[]  = $row;
                $i++;
            }
        }

        return $events;
    }

    /**
     * @param list<array<string, mixed>> $events
     * @param list<array<string, mixed>> $users
     */
    private function seedAttendances(string $now, array $events, array $users): void
    {
        $candidates = array_values(array_filter($users, static fn (array $u): bool => $u['deleted_at'] === null));
        $rows       = [];

        foreach ($events as $event) {
            if ($event['status'] === 'draft') {
                continue;
            }

            $past  = strtotime($event['starts_at']) < time();
            $limit = min($event['capacity'] ?? 18, count($candidates));
            $count = mt_rand((int) ($limit / 3), (int) ($limit * 0.8));

            shuffle($candidates);

            foreach (array_slice($candidates, 0, $count) as $user) {
                $registered = min(strtotime($event['starts_at'] . ' -' . mt_rand(1, 20) . ' days'), time());
                $chance     = mt_rand(1, 100);

                $rows[] = [
                    'user_id'       => $user['id'],
                    'event_id'      => $event['id'],
                    'status'        => $past ? ($chance <= 75 ? 'attended' : 'absent') : ($chance <= 85 ? 'registered' : 'cancelled'),
                    'registered_at' => date('Y-m-d H:i:s', $registered),
                    'notes'         => $chance % 10 === 0 ? 'Needs step-free access.' : null,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
        }

        $this->db->table('kpdemo_attendances')->insertBatch($rows);
    }

    // ------------------------------------------------------------------
    // Placeholder images
    // ------------------------------------------------------------------

    private function cleanUploads(): void
    {
        foreach ([self::AVATAR_DIR, self::POSTER_DIR] as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            // Blocks directory listing, like CodeIgniter does in its own folders
            if (! is_file($dir . '/index.html')) {
                file_put_contents($dir . '/index.html', '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><p>Directory access is forbidden.</p></body></html>');
            }

            // Everything is removed except the index.html at the root of the folder.
            // CodeIgniter also creates an index.html in every subfolder it makes for uploads.
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach ($items as $item) {
                if ($item->isDir()) {
                    rmdir($item->getPathname());
                } elseif ($item->getPath() !== $dir || $item->getFilename() !== 'index.html') {
                    unlink($item->getPathname());
                }
            }
        }
    }

    private function avatar(string $first, string $last, int $index): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $palette  = ['#0d6efd', '#6f42c1', '#d63384', '#dc3545', '#fd7e14', '#198754', '#20c997', '#0dcaf0'];
        $initials = mb_strtoupper($this->slug(mb_substr($first, 0, 1)) . $this->slug(mb_substr($last, 0, 1)));

        // The text is drawn small and scaled up: GD's built-in fonts are tiny
        $small = imagecreatetruecolor(25, 25);
        imagefill($small, 0, 0, $this->color($small, $palette[$index % count($palette)]));
        imagestring($small, 5, 3, 5, $initials, $this->color($small, '#ffffff'));

        $image = imagecreatetruecolor(200, 200);
        imagecopyresized($image, $small, 0, 0, 0, 0, 200, 200, 25, 25);

        return $this->save($image, self::AVATAR_DIR, 'avatar-' . $index);
    }

    private function poster(string $title, string $category, string $color, int $index, string $subdir): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $small = imagecreatetruecolor(300, 200);
        imagefill($small, 0, 0, $this->color($small, $color));
        imagefilledrectangle($small, 0, 140, 300, 200, $this->color($small, '#212529'));

        $white = $this->color($small, '#ffffff');
        $y     = 30;

        foreach (explode("\n", wordwrap($this->ascii($title), 30, "\n", true)) as $line) {
            imagestring($small, 5, 15, $y, $line, $white);
            $y += 20;
        }

        imagestring($small, 3, 15, 160, mb_strtoupper($this->ascii($category)), $white);

        $image = imagecreatetruecolor(600, 400);
        imagecopyresized($image, $small, 0, 0, 0, 0, 600, 400, 300, 200);

        return $this->save($image, self::POSTER_DIR, $subdir . '/poster-' . $index);
    }

    /**
     * Saves the image and returns its path relative to $dir, which is what
     * the CRUD stores in the database.
     */
    private function save(\GdImage $image, string $dir, string $name): string
    {
        $file = $name . '.png';
        $path = $dir . '/' . $file;

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        imagepng($image, $path);

        return $file;
    }

    private function color(\GdImage $image, string $hex): int
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return imagecolorallocate($image, $r, $g, $b);
    }

    /**
     * GD's built-in fonts only support ASCII.
     */
    private function ascii(string $text): string
    {
        return (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', str_replace(['·', 'l·l'], ['.', 'l.l'], $text));
    }

    private function slug(string $text): string
    {
        return strtolower(preg_replace('/[^a-z]/i', '', $this->ascii($text)));
    }
}
