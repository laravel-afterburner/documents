# Cloudflare R2 Setup Guide

This package uses **Cloudflare R2** for document storage. R2 is Cloudflare's object storage service, similar to AWS S3 but with no egress fees.

## Account Setup Steps

### 1. Create a Cloudflare Account
- Go to [cloudflare.com](https://www.cloudflare.com) and sign up for a free account
- Verify your email address

### 2. Enable R2 (if not already enabled)
- Log into your Cloudflare dashboard
- Navigate to **R2** in the left sidebar (under "Storage")
- If you see a "Get Started" button, click it to enable R2
- R2 is free to use with generous limits

### 3. Create an R2 Bucket
- In the R2 dashboard, click **Create bucket**
- Enter a bucket name (e.g., `afterburner-documents`)
- Choose a location (select the region closest to your users)
- Click **Create bucket**

### 4. Create API Tokens
You need to create API tokens to allow your Laravel application to access R2:

#### Option A: Create R2 Token (Recommended)
1. In the R2 dashboard, click **Manage R2 API Tokens**
2. Click **Create API Token**
3. Give it a name (e.g., "Afterburner Documents")
4. Set permissions:
   - **Object Read & Write** (or more restrictive if preferred)
5. Click **Create API Token**
6. **IMPORTANT**: Copy the **Access Key ID** (f7da976e9eb90e97fc58a345187030e1) and **Secret Access Key** (8f8f15c7697a3c60a4cc5b65ffa87bc2f847737341008e8807d36881a65bec10) immediately - you won't be able to see the secret again!

#### Option B: Use Account API Token (Alternative)
1. Go to **My Profile** → **API Tokens**
2. Click **Create Token**
3. Use the **Edit Cloudflare Workers** template or create a custom token
4. Add R2 permissions: `Account:Cloudflare R2:Edit`
5. Copy the token

### 5. Get Your R2 Endpoint URL
- In your R2 bucket settings, find the **S3 API** section
- Your endpoint is https://1a20c641ebef0c1b86049cd3211b7192.r2.cloudflarestorage.com
- You can find your account ID in the R2 dashboard URL or in your account settings

### 6. Configure Your Laravel Application

#### Option A: Use the Install Command (Recommended)

Run the install command to automatically add the required environment variables to your `.env` file:

```bash
php artisan afterburner:documents:install
```

This will add placeholder values that you can then replace with your actual credentials.

#### Option B: Manual Configuration

If you prefer to add them manually, add these environment variables to your `.env` file:

```env
# Cloudflare R2 Configuration
AFTERBURNER_DOCUMENTS_R2_ENDPOINT=https://<your-account-id>.r2.cloudflarestorage.com
AFTERBURNER_DOCUMENTS_R2_ACCESS_KEY_ID=your-access-key-id
AFTERBURNER_DOCUMENTS_R2_SECRET_ACCESS_KEY=your-secret-access-key
AFTERBURNER_DOCUMENTS_R2_BUCKET=your-bucket-name
AFTERBURNER_DOCUMENTS_R2_REGION=auto
AFTERBURNER_DOCUMENTS_R2_URL=https://your-bucket-domain.com  # Optional: if you set up a custom domain
AFTERBURNER_DOCUMENTS_R2_USE_PATH_STYLE_ENDPOINT=false
```

**Note**: You can also use the generic `CLOUDFLARE_R2_*` environment variables if you have them set up for other parts of your application. The package will fall back to those if the specific `AFTERBURNER_DOCUMENTS_R2_*` variables aren't set.

### 7. Optional: Set Up Custom Domain (for public access)
If you want to serve documents publicly via a custom domain:

1. In your R2 bucket settings, go to **Settings** → **Public Access**
2. Click **Connect Domain**
3. Enter your domain (e.g., `files.yourdomain.com`)
4. Follow the DNS configuration instructions
5. Add the domain to your `.env` as `AFTERBURNER_DOCUMENTS_R2_URL`

## Pricing

Cloudflare R2 has a **free tier** that includes:
- 10 GB of storage
- 1 million Class A operations (writes, lists) per month
- 10 million Class B operations (reads) per month

After the free tier:
- Storage: $0.015 per GB/month
- Class A operations: $4.50 per million
- Class B operations: $0.36 per million
- **No egress fees** (unlike AWS S3)

## Security Best Practices

1. **Never commit API keys to version control** - always use environment variables
2. **Use least-privilege access** - only grant the permissions your application needs
3. **Rotate API tokens regularly** - create new tokens and update your `.env` file
4. **Enable bucket versioning** if you need to recover deleted files
5. **Set up lifecycle rules** if you want automatic deletion of old files

## Testing Your Setup

After configuring, test your setup by:
1. Uploading a test document through the application
2. Checking your R2 bucket to see if the file appears
3. Verifying you can download the document

## Troubleshooting

### "Access Denied" errors
- Verify your Access Key ID and Secret Access Key are correct
- Check that your API token has the correct permissions
- Ensure the bucket name matches exactly

### "Bucket not found" errors
- Verify the bucket name is correct
- Check that the bucket exists in the same Cloudflare account
- Ensure you're using the correct endpoint URL

### Upload failures
- Check file size limits (default is 100MB, configurable)
- Verify network connectivity
- Check Cloudflare R2 service status

## Additional Resources

- [Cloudflare R2 Documentation](https://developers.cloudflare.com/r2/)
- [R2 Pricing](https://developers.cloudflare.com/r2/pricing/)
- [R2 API Reference](https://developers.cloudflare.com/r2/api/)

