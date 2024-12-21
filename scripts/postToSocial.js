import puppeteer from 'puppeteer';
import fetch from 'node-fetch';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const downloadImage = async (url, outputPath) => {
    const response = await fetch(url);
    if (!response.ok) {
        throw new Error(`Failed to fetch image: ${response.statusText}`);
    }
    const buffer = await response.buffer();
    fs.writeFileSync(outputPath, buffer);
    console.log(`Image downloaded to ${outputPath}`);
};

(async () => {
    const args = process.argv.slice(2);
    const productName = args[0];
    const productDescription = args[1];
    const imageUrls = args.slice(2);

    const browser = await puppeteer.launch({
        headless: true, // Set to true for production
    });

    const page = await browser.newPage();
    page.setDefaultNavigationTimeout(60000);

    try {
        console.log('Navigating to Buffer login page...');
        await page.goto('https://login.buffer.com/login', { waitUntil: 'domcontentloaded' });

        console.log('Filling in login credentials...');
        await page.type('#email', 'saipovbogdan18@gmail.com');
        await page.type('#password', 'dPqAgpy!8*nnnv+');

        console.log('Logging in...');
        await page.click('#login-form-submit');
        await page.waitForNavigation({ waitUntil: 'domcontentloaded' });

        console.log('Waiting for "New Post" button...');
        const newPostButtonSelector = 'button[data-testid="queue-header-create-post"]';
        await page.waitForSelector(newPostButtonSelector, { timeout: 60000 });
        await page.click(newPostButtonSelector);

        console.log('Entering post content...');
        const textBoxSelector = 'div[role="textbox"][aria-label="composer textbox"]';
        await page.waitForSelector(textBoxSelector, { timeout: 60000 });
        await page.type(textBoxSelector, `📢 ${productName}\n\n📝 ${productDescription}`);

        if (imageUrls.length > 0) {
            console.log('Uploading images...');
            const fileInputSelector = 'input[data-testid="uploads-dropzone-input"]';

            for (const imageUrl of imageUrls) {
                const localImagePath = path.resolve(__dirname, 'temp_image.jpg');
                await downloadImage(imageUrl, localImagePath);

                if (!fs.existsSync(localImagePath)) {
                    throw new Error(`File does not exist: ${localImagePath}`);
                }

                const inputHandle = await page.$(fileInputSelector);
                if (inputHandle) {
                    await inputHandle.uploadFile(localImagePath);
                    console.log(`Uploaded image: ${localImagePath}`);
                } else {
                    throw new Error('File input not found');
                }

                fs.unlinkSync(localImagePath);
                await delay(20000); // Extended delay
            }
        }

        console.log('Clicking dropdown menu...');
        const dropdownSvgSelector = 'div[type="primary"] svg[class*="Icon__StyledIcon-bufferapp-ui__sc-dbjb4v-0"]';
        await page.waitForSelector(dropdownSvgSelector, { timeout: 60000 });
        await page.click(dropdownSvgSelector);

        console.log('Selecting "Share Now"...');
        const shareNowSelector = 'li#SHARE_NOW';
        await page.waitForSelector(shareNowSelector, { timeout: 60000 });
        await page.click(shareNowSelector);

        console.log('Waiting for Buffer to process...');
        await delay(20000);

        console.log('Post shared successfully!');
    } catch (error) {
        console.error('Error occurred during the process:', error.message);
        await page.screenshot({ path: 'debug-screenshot.png' });
        console.log('Saved a screenshot for debugging.');
    } finally {
        await browser.close();
    }
})();
