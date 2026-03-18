#include <iostream>
#include <thread>
#include <opencv2/opencv.hpp>
#include <onnxruntime_cxx_api.h>

cv::Mat run_modnet(Ort::Session &session, const cv::Mat &img)
{
    const int W = 512;
    const int H = 512;

    // 1. リサイズ
    cv::Mat resized;
    cv::resize(img, resized, cv::Size(W, H));

    // 2. BGR → RGB
    cv::cvtColor(resized, resized, cv::COLOR_BGR2RGB);

    // 3. float化 & 0〜1 正規化
    resized.convertTo(resized, CV_32FC3, 1.0 / 255.0);

    // 4. mean/std 正規化
    resized = (resized - 0.5f) / 0.5f;

    // 5. CHW へ変換
    std::vector<float> input(1 * 3 * H * W);
    int idx = 0;
    for (int c = 0; c < 3; c++)
    {
        for (int y = 0; y < H; y++)
        {
            for (int x = 0; x < W; x++)
            {
                input[idx++] = resized.at<cv::Vec3f>(y, x)[c];
            }
        }
    }

    // 6. ONNX 実行
    Ort::AllocatorWithDefaultOptions allocator;
    auto in_name_alloc = session.GetInputNameAllocated(0, allocator);
    auto out_name_alloc = session.GetOutputNameAllocated(0, allocator);
    const char *in_name = in_name_alloc.get();
    const char *out_name = out_name_alloc.get();

    Ort::MemoryInfo mem_info = Ort::MemoryInfo::CreateCpu(OrtArenaAllocator, OrtMemTypeDefault);
    std::array<int64_t, 4> in_shape{1, 3, H, W};

    Ort::Value in_tensor = Ort::Value::CreateTensor<float>(
        mem_info, input.data(), input.size(), in_shape.data(), in_shape.size());

    auto outputs = session.Run(
        Ort::RunOptions{nullptr},
        &in_name, &in_tensor, 1,
        &out_name, 1);

    float *out = outputs.front().GetTensorMutableData<float>();

    cv::Mat mask(H, W, CV_32FC1, out);
    return mask.clone();
}

cv::Mat composite_with_background(const cv::Mat &img, const cv::Mat &mask_resized, const cv::Mat &background)
{
    // 背景を元画像サイズにリサイズ
    cv::Mat bg;
    cv::resize(background, bg, img.size());

    // 出力画像
    cv::Mat result(img.size(), CV_8UC3);

    for (int y = 0; y < img.rows; y++)
    {
        for (int x = 0; x < img.cols; x++)
        {
            float alpha = mask_resized.at<float>(y, x); // 0〜1

            cv::Vec3b fg = img.at<cv::Vec3b>(y, x);
            cv::Vec3b bg_pixel = bg.at<cv::Vec3b>(y, x);

            cv::Vec3b blended;
            for (int c = 0; c < 3; c++)
            {
                blended[c] = (uchar)(alpha * fg[c] + (1.0f - alpha) * bg_pixel[c]);
            }

            result.at<cv::Vec3b>(y, x) = blended;
        }
    }

    return result;
}
cv::Mat remove_background(const cv::Mat &img, const cv::Mat &mask_resized)
{
    cv::Mat rgba;
    cv::cvtColor(img, rgba, cv::COLOR_BGR2BGRA);

    for (int y = 0; y < img.rows; y++)
    {
        for (int x = 0; x < img.cols; x++)
        {
            float a = mask_resized.at<float>(y, x);
            rgba.at<cv::Vec4b>(y, x)[3] = (uchar)(a * 255);
        }
    }
    return rgba;
}

int main(int argc, char *argv[])
{
    if (argc < 3)
    {
        std::cerr << "Usage: removebg <input> <output>" << std::endl;
        return 1;
    }

    std::string input = argv[1];
    std::string output = argv[2];

    cv::Mat img = cv::imread(input);
    if (img.empty())
    {
        std::cerr << "Failed to load input image" << std::endl;
        return 1;
    }

    // --- ここからトリミング処理を追加 ---

// --- 顔検出の準備 ---
cv::CascadeClassifier face_cascade;
face_cascade.load("/var/www/html/cpp/haarcascade_frontalface_default.xml");

std::vector<cv::Rect> faces;
face_cascade.detectMultiScale(img, faces, 1.1, 3, 0, cv::Size(80, 80));

// 顔が見つからない場合は中央トリミングにフォールバック
cv::Rect face;
if (faces.size() > 0) {
    face = faces[0]; // 最初の顔を使用
} else {
    // 中央を仮の顔として扱う
    face = cv::Rect(img.cols/4, img.rows/4, img.cols/2, img.rows/2);
}

// --- 顔中心を基準に 5:4 でトリミング ---
int w = img.cols;
int h = img.rows;

// 顔の中心
int cx = face.x + face.width / 2;
int cy = face.y + face.height / 2;

// トリミングサイズ（5:4）
int target_h = h;
int target_w = (h * 4) / 5;

if (target_w > w) {
    target_w = w;
    target_h = (w * 5) / 4;
}

// トリミング開始位置（顔中心に合わせる）
int x = cx - target_w / 2;
int y = cy - target_h * 0.45;  // 顔を少し上寄りに配置（証明写真の構図）

// 画像範囲に収める
if (x < 0) x = 0;
if (y < 0) y = 0;
if (x + target_w > w) x = w - target_w;
if (y + target_h > h) y = h - target_h;

// トリミング
cv::Rect crop(x, y, target_w, target_h);
cv::Mat cropped = img(crop);

// 背景削除に渡す画像を置き換え
img = cropped;

// --- トリミングここまで ---

    // goto LV_WRITE;
    //  ONNX Runtime 環境
    Ort::Env env(ORT_LOGGING_LEVEL_WARNING, "removebg");
    Ort::SessionOptions session_options;
    session_options.SetIntraOpNumThreads(1);

    // モデル読み込み
    Ort::Session modnet(env, "/var/www/html/cpp/models/modnet_photographic_portrait_matting.onnx", session_options);

    std::cout << "Model loaded successfully" << std::endl;
    std::cout << "Image loaded: " << input << std::endl;

    // ここに後で背景削除処理を追加する
    // 1. MODNet 実行
    cv::Mat mask = run_modnet(modnet, img);
    std::cout << "run_modnet: " << input << std::endl;

    // 2. 元画像サイズにリサイズ
    cv::Mat mask_resized;
    cv::resize(mask, mask_resized, img.size());
    std::cout << "resize: " << input << std::endl;

    auto type_info = modnet.GetInputTypeInfo(0);
    auto tensor_info = type_info.GetTensorTypeAndShapeInfo();
    auto input_shape = tensor_info.GetShape();

    // 背景削除
    cv::Mat png = remove_background(img, mask_resized);
    cv::Mat background = cv::imread("/var/www/html/cpp/back1.jpg"); // 任意の背景画像
    cv::Mat composite = composite_with_background(img, mask_resized, background);
//
LV_WRITE:

    cv::imwrite(output, composite);
    //cv::imwrite(output, img);
    std::cout << "Output saved: " << output << std::endl;

    return 0;
}