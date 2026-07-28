#!/usr/bin/env python3
"""
Convert PDF specification files to Markdown format for easier AI consumption.
Uses PyMuPDF (fitz) to extract text content and images from PDFs.
"""

import os
import fitz  # PyMuPDF


def convert_pdf_to_markdown(pdf_path: str, images_dir: str, pdf_basename: str) -> str:
    """
    Extract text and images from a PDF file and convert to Markdown format.

    Args:
        pdf_path: Path to the PDF file
        images_dir: Directory to save extracted images
        pdf_basename: Base name of the PDF (used for image naming)

    Returns:
        Markdown-formatted string of the PDF content
    """
    doc = fitz.open(pdf_path)
    markdown_content = []
    image_count = 0

    for page_num, page in enumerate(doc, start=1):
        # Extract text blocks with position info for better structure detection
        blocks = page.get_text("dict")["blocks"]

        # Sort blocks by vertical position (top to bottom)
        blocks_sorted = sorted(blocks, key=lambda b: (b["bbox"][1], b["bbox"][0]))

        page_content = []

        # Collect unique image block bounding boxes for fallback rendering
        image_bboxes = []

        for block in blocks_sorted:
            if block["type"] == 0:  # Text block
                block_text = []
                for line in block["lines"]:
                    # Spaces are encoded as their own spans, so concatenate the span
                    # texts directly. Inserting a separator here would split words
                    # (e.g. "Requ" + "i" + "rements" -> "Requ i rements").
                    line_text = "".join(span["text"] for span in line["spans"]).strip()
                    if not line_text:
                        continue

                    # Detect potential headings by font size at the line level.
                    sizes = [span["size"] for span in line["spans"] if span["text"].strip()]
                    is_bold = any(
                        "bold" in span["font"].lower()
                        for span in line["spans"]
                        if span["text"].strip()
                    )
                    max_size = max(sizes) if sizes else 0
                    if max_size >= 14 or (max_size >= 12 and is_bold):
                        # Likely a heading
                        line_text = f"**{line_text}**"

                    block_text.append(line_text)

                if block_text:
                    page_content.append("\n".join(block_text))

            elif block["type"] == 1:  # Image block
                bbox = tuple(round(x) for x in block["bbox"])
                if bbox not in image_bboxes:
                    image_bboxes.append(bbox)

        # Extract real embedded images using get_images()
        image_list = page.get_images(full=True)
        extracted_xrefs = set()
        for img_info in image_list:
            try:
                xref = img_info[0]
                if xref in extracted_xrefs:
                    continue
                extracted_xrefs.add(xref)

                base_image = doc.extract_image(xref)
                image_bytes = base_image["image"]
                image_ext = base_image["ext"]

                image_count += 1
                img_filename = f"{pdf_basename}_img{image_count:03d}.{image_ext}"
                img_path = os.path.join(images_dir, img_filename)

                with open(img_path, "wb") as img_file:
                    img_file.write(image_bytes)

                page_content.append(f"\n![Image {image_count}](images/{img_filename})\n")
            except Exception as e:
                print(f"    Warning: Could not extract image from xref {img_info[0]}: {e}")

        # Fallback: if page has image blocks but get_images() found nothing,
        # render the image regions as pixmaps (handles vector graphics / layered images)
        if image_bboxes and not image_list:
            for bbox in image_bboxes:
                try:
                    clip = fitz.Rect(bbox)
                    mat = fitz.Matrix(3, 3)  # 3x zoom for good quality
                    pix = page.get_pixmap(matrix=mat, clip=clip)

                    image_count += 1
                    img_filename = f"{pdf_basename}_img{image_count:03d}.png"
                    img_path = os.path.join(images_dir, img_filename)
                    pix.save(img_path)

                    page_content.append(f"\n![Image {image_count}](images/{img_filename})\n")
                except Exception as e:
                    print(f"    Warning: Could not render image region {bbox}: {e}")

        if page_content:
            markdown_content.append("\n".join(page_content))

    doc.close()

    # Join pages with double newlines and clean up
    full_text = "\n\n".join(markdown_content)

    # Post-process: convert bold markers to proper headings where appropriate
    lines = full_text.split("\n")
    processed_lines = []

    for line in lines:
        stripped = line.strip()
        # If line is entirely bold and short, make it a heading
        if stripped.startswith("**") and stripped.endswith("**") and stripped.count("**") == 2:
            inner = stripped[2:-2]
            if len(inner) < 100:  # Likely a heading, not a bold paragraph
                processed_lines.append(f"## {inner}")
            else:
                processed_lines.append(line)
        else:
            processed_lines.append(line)

    return "\n".join(processed_lines)


def main():
    """Main function to convert all PDFs in the specs folder."""
    script_dir = os.path.dirname(os.path.abspath(__file__))
    output_dir = os.path.join(script_dir, "md")
    images_dir = os.path.join(output_dir, "images")

    # Create output directories if they don't exist
    os.makedirs(output_dir, exist_ok=True)
    os.makedirs(images_dir, exist_ok=True)

    # Find all PDF files in the specs directory
    pdf_files = [f for f in os.listdir(script_dir) if f.lower().endswith(".pdf")]

    if not pdf_files:
        print("No PDF files found in the specs directory.")
        return

    print(f"Found {len(pdf_files)} PDF file(s) to convert:")
    for pdf_file in pdf_files:
        print(f"  - {pdf_file}")

    print()

    total_images = 0

    # Convert each PDF
    for pdf_file in pdf_files:
        pdf_path = os.path.join(script_dir, pdf_file)
        pdf_basename = os.path.splitext(pdf_file)[0].replace(" ", "_").replace("-", "_")
        md_filename = os.path.splitext(pdf_file)[0] + ".md"
        md_path = os.path.join(output_dir, md_filename)

        print(f"Converting: {pdf_file}")

        try:
            # Count images before conversion
            images_before = len([f for f in os.listdir(images_dir) if f.startswith(pdf_basename)])

            markdown_content = convert_pdf_to_markdown(pdf_path, images_dir, pdf_basename)

            with open(md_path, "w", encoding="utf-8") as f:
                f.write(markdown_content)

            # Count images after conversion
            images_after = len([f for f in os.listdir(images_dir) if f.startswith(pdf_basename)])
            images_extracted = images_after - images_before
            total_images += images_extracted

            print(f"  -> Created: md/{md_filename} ({images_extracted} images)")
        except Exception as e:
            print(f"  -> Error: {e}")

    print()
    print(f"Conversion complete! Extracted {total_images} images total.")
    print(f"Images saved to: md/images/")


if __name__ == "__main__":
    main()
