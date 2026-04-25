<?php

return [

    'wedding' => [

        'details' => [
            'groom_name'         => 'Ahmad Rizky',
            'bride_name'         => 'Siti Nurhaliza',
            'groom_photo_url'    => '/image/demo-image/groom.png',
            'bride_photo_url'    => '/image/demo-image/bride.png',
            'groom_parent_names' => 'Bpk. Hasan & Ibu Fatimah',
            'bride_parent_names' => 'Bpk. Rahmat & Ibu Aminah',
            'groom_instagram'    => 'ahmadrizky',
            'bride_instagram'    => 'sitinurhaliza',
            'opening_text'       => "Bismillahirrahmanirrahim\nDengan memohon rahmat dan ridho Allah SWT, kami mengundang Bapak/Ibu/Saudara/i untuk menghadiri pernikahan kami.",
            'closing_text'       => 'Merupakan suatu kehormatan dan kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir untuk memberikan doa restu.',
        ],

        'events' => [
            [
                'event_name'    => 'Akad Nikah',
                'event_date'    => '2026-06-15',
                'start_time'    => '08:00',
                'end_time'      => '10:00',
                'venue_name'    => 'Masjid Al-Ikhlas',
                'venue_address' => 'Jl. Sudirman No. 123, Jakarta Selatan',
                'maps_url'      => 'https://maps.google.com/?q=-6.2088,106.8456',
            ],
            [
                'event_name'    => 'Resepsi',
                'event_date'    => '2026-06-15',
                'start_time'    => '11:00',
                'end_time'      => '14:00',
                'venue_name'    => 'Ballroom Hotel Mulia',
                'venue_address' => 'Jl. Asia Afrika No. 8, Jakarta Selatan',
                'maps_url'      => 'https://maps.google.com/?q=-6.2150,106.8070',
            ],
        ],

        'gallery' => [
            '/image/demo-image/bride-groom.png',
            '/image/demo-image/bride-groom.png',
            '/image/demo-image/bride-groom.png',
            '/image/demo-image/bride-groom.png',
        ],

        'love_story' => [
            [
                'date'        => 'Maret 2020',
                'title'       => 'Pertama Bertemu',
                'description' => 'Kami pertama kali bertemu di sebuah seminar kampus. Satu tatapan yang tak terlupakan menjadi awal dari segalanya.',
                'photo_url'   => '/image/demo-image/bride-groom.png',
            ],
            [
                'date'        => 'Juni 2020',
                'title'       => 'Jatuh Hati',
                'description' => 'Setelah beberapa bulan sering menghabiskan waktu bersama, kami sadar ada sesuatu yang istimewa di antara kami.',
            ],
            [
                'date'        => 'Desember 2021',
                'title'       => 'Resmi Bersama',
                'description' => 'Di bawah langit berbintang, Ahmad memberanikan diri mengungkapkan perasaannya. Siti pun menerima dengan senyum termanis.',
                'photo_url'   => '/image/demo-image/bride-groom.png',
            ],
            [
                'date'        => 'Juni 2026',
                'title'       => 'Menuju Pelaminan',
                'description' => 'Dengan restu kedua keluarga dan doa dari orang-orang tercinta, kami siap melangkah ke babak baru kehidupan bersama.',
            ],
        ],

        'gift' => [
            'accounts' => [
                ['bank' => 'BCA',     'account_number' => '1234567890', 'account_name' => 'Ahmad Rizky'],
                ['bank' => 'Mandiri', 'account_number' => '0987654321', 'account_name' => 'Siti Nurhaliza'],
            ],
        ],

        'messages' => [
            ['id' => 1, 'name' => 'Budi & Ani',         'message' => 'Selamat menempuh hidup baru! Semoga menjadi keluarga yang sakinah mawaddah warahmah 🤲',  'created_at' => '2 jam lalu'],
            ['id' => 2, 'name' => 'Keluarga Pak Harto', 'message' => 'Barakallah! Semoga pernikahannya diberkahi Allah SWT 🙏',                                 'created_at' => '5 jam lalu'],
            ['id' => 3, 'name' => 'Rina',               'message' => 'Happy wedding! Udah ga sabar mau dateng nih 🎉',                                          'created_at' => '1 hari lalu'],
            ['id' => 4, 'name' => 'Teman SMA',          'message' => 'Akhirnya nikah juga! Selamat ya bro & sis! 💍',                                           'created_at' => '2 hari lalu'],
        ],

    ],

];
