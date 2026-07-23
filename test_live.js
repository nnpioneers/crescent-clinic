// Native fetchasync function test() {
    // 1. Get CSRF token from login page
    const res1 = await fetch('https://grey-partridge-285380.hostingersite.com/login');
    const text1 = await res1.text();
    const csrfMatch = text1.match(/name="csrf_token" value="([^"]+)"/);
    if (!csrfMatch) {
        console.log("No CSRF token found");
        return;
    }
    const csrfToken = csrfMatch[1];
    
    // Extract PHPSESSID cookie
    const cookies = res1.headers.raw()['set-cookie'];
    const sessCookie = cookies.find(c => c.startsWith('PHPSESSID=')).split(';')[0];
    
    // 2. Login
    const formData = new URLSearchParams();
    formData.append('username', 'admin');
    formData.append('password', 'admin123'); // Assuming default admin password
    formData.append('csrf_token', csrfToken);
    
    const res2 = await fetch('https://grey-partridge-285380.hostingersite.com/login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Cookie': sessCookie
        },
        body: formData.toString()
    });
    
    // 3. Fetch dashboard data
    const res3 = await fetch('https://grey-partridge-285380.hostingersite.com/api/reports_api.php?action=get_reports&period=today', {
        headers: {
            'Cookie': sessCookie
        }
    });
    
    console.log("Status:", res3.status);
    const data = await res3.text();
    console.log(data.substring(0, 500));
}

test().catch(console.error);
